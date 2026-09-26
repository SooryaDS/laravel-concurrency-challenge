import { useState } from "react";
import api from "../services/api";
import Layout from "../components/Layout";

function Invoices() {
    const [orderId, setOrderId] = useState("");
    const [amount, setAmount] = useState("");

    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState("");
    const [error, setError] = useState("");

    const generateInvoice = async (e) => {
        e.preventDefault();

        setLoading(true);
        setMessage("");
        setError("");

        try {
            const response = await api.post("/invoice", {
                order_id: Number(orderId),
                amount: Number(amount),
            });

            setMessage(
                response.data.message ||
                "Invoice generation job dispatched."
            );
        } catch (err) {
            setError(
                err.response?.data?.message ||
                "Failed to dispatch invoice job."
            );
        } finally {
            setLoading(false);
        }
    };

    return (
        <Layout>
            <div className="page-header">
                <h2>Queue Job Reliability</h2>

                <p>
                    3.6 — Invoice generation remains idempotent when a
                    queued job is retried or processed more than once.
                </p>
            </div>

            <div className="test-panel">
                <h3>Generate Invoice</h3>

                <form onSubmit={generateInvoice}>

                    <div className="form-group">
                        <label>Order ID</label>

                        <input
                            type="number"
                            value={orderId}
                            onChange={(e) =>
                                setOrderId(e.target.value)
                            }
                            min="1"
                            placeholder="e.g. 1001"
                            required
                        />
                    </div>

                    <div className="form-group">
                        <label>Amount</label>

                        <input
                            type="number"
                            value={amount}
                            onChange={(e) =>
                                setAmount(e.target.value)
                            }
                            min="0.01"
                            step="0.01"
                            placeholder="100.00"
                            required
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={loading}
                    >
                        {loading
                            ? "Dispatching..."
                            : "Generate Invoice"}
                    </button>

                </form>
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

export default Invoices;