import { useState } from "react";
import api from "../services/api";
import Layout from "../components/Layout";

function Payments() {
    const [idempotencyKey, setIdempotencyKey] = useState("");
    const [amount, setAmount] = useState("");

    const [payment, setPayment] = useState(null);
    const [loading, setLoading] = useState(false);

    const [message, setMessage] = useState("");
    const [error, setError] = useState("");

    const createPayment = async (e) => {
        e.preventDefault();

        setLoading(true);
        setMessage("");
        setError("");
        setPayment(null);

        try {
            const response = await api.post("/payments", {
                idempotency_key: idempotencyKey,
                amount: Number(amount),
            });

            setPayment(response.data);
            setMessage("Payment processed successfully.");

        } catch (err) {
            setError(
                err.response?.data?.message ||
                "Failed to process payment."
            );
        } finally {
            setLoading(false);
        }
    };

    return (
        <Layout>
            <div className="page-header">
                <h2>Duplicate Payments</h2>
                <p>
                    3.2 — Idempotency prevents duplicate payment records
                    when the same request is submitted more than once.
                </p>
            </div>

            <div className="test-panel">
                <h3>Create Payment</h3>

                <form onSubmit={createPayment}>

                    <div className="form-group">
                        <label>Idempotency Key</label>

                        <input
                            type="text"
                            value={idempotencyKey}
                            onChange={(e) =>
                                setIdempotencyKey(e.target.value)
                            }
                            placeholder="e.g. payment-123"
                            required
                        />
                    </div>

                    <div className="form-group">
                        <label>Amount</label>

                        <input
                            type="number"
                            min="0.01"
                            step="0.01"
                            value={amount}
                            onChange={(e) =>
                                setAmount(e.target.value)
                            }
                            placeholder="100.00"
                            required
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={loading}
                    >
                        {loading ? "Processing..." : "Create Payment"}
                    </button>

                </form>
            </div>

            {payment && (
                <div className="test-panel">

                    <h3>Payment Details</h3>

                    <div className="ticket-info">

                        <div>
                            <span>Payment ID</span>
                            <strong>{payment.id}</strong>
                        </div>

                        <div>
                            <span>Amount</span>
                            <strong>{payment.amount}</strong>
                        </div>

                        <div>
                            <span>Status</span>
                            <strong>{payment.status}</strong>
                        </div>

                    </div>

                    <div className="form-group">
                        <label>Idempotency Key</label>

                        <input
                            type="text"
                            value={payment.idempotency_key}
                            readOnly
                        />
                    </div>

                </div>
            )}

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

export default Payments;