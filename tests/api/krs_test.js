import http from "k6/http";
import { check, sleep } from "k6";

export const options = {
    stages: [
        { duration: "5s", target: 50 },
        { duration: "25s", target: 50 },
        { duration: "5s", target: 0 },
    ],
};

// --- PERUBAHAN DI SINI ---
// 1. PASTIKAN PORT DI URL SUDAH BENAR (e.g., 8000)
// Gunakan alamat loopback yang pasti (127.0.0.1)
const BASE_URL = "http://127.0.0.1:8080/api/post-krs";

// 2. TOKEN HANYA BERISI NILAI TOKEN, TANPA PREFIKS "Bearer"
const AUTH_TOKEN_VALUE = "4|aQMWrfwGT8Gf2fzwqrIxZHfPGuSOA58re1Gp76rw5fbdc3ca";

const payload = JSON.stringify({
    student_id: 3099,
    term_year_id: 20252,
    items: [
        {
            course_id: 2380,
            class_prog_id: 1,
            class_id: 2,
            sks: 3,
            amount: 0,
        },
    ],
});

const params = {
    headers: {
        "Content-Type": "application/json", // Tambahkan "Bearer " di sini
        Authorization: `Bearer ${AUTH_TOKEN_VALUE}`,
    },
};
// --------------------------

export default function () {
    const res = http.post(BASE_URL, payload, params);

    check(res, {
        "Status is 201 or 422": (r) => r.status === 201 || r.status === 422,
        "422 (Duplikasi) error is expected": (r) =>
            r.status === 422 && r.json("message") === "Duplicate Course",
        "201 (Success) is expected": (r) =>
            r.status === 201 &&
            r.json("message") === "KRS successfully validated (Rollback Mode)",
    });

    sleep(0.5);
}
