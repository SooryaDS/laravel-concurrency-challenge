import { useState } from "react";
import api from "../services/api";
import Layout from "../components/Layout";

function LoginCode() {
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState("");
    const [error, setError] = useState("");

    const requestCode = async () => {
        setLoading(true);
        setMessage("");
        setError("");

        try {
            const response = await api.post("/login-code");

            setMessage(
                response.data.message || "Login code sent successfully."
            );
        } catch (err) {
            if (err.response?.status === 429) {
                setError(
                    "Too many requests. Please wait before requesting another login code."
                );
            } else {
                setError(
                    err.response?.data?.message ||
                    "Failed to request login code."
                );
            }
        } finally {
            setLoading(false);
        }
    };

    return (
        <Layout>
            <div className="page-header">
                <h2>Rate-Limited API</h2>

                <p>
                    3.7 — Login code requests are limited to 3 requests
                    within 10 minutes.
                </p>
            </div>

            <div className="test-panel">
                <h3>Request Login Code</h3>

                <p>
                    You can request a login code up to 3 times within
                    a 10-minute window.
                </p>

                <br />

                <button
                    type="button"
                    onClick={requestCode}
                    disabled={loading}
                >
                    {loading ? "Sending..." : "Request Login Code"}
                </button>
            </div>

            {message && (
                <div className="success-message">
                    {message}
                </div>
            )}

            {error && (
                <div className="error-message">
                    {error}
                </div>
            )}
        </Layout>
    );
}

export default LoginCode;