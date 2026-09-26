import { useState } from "react";
import api from "../services/api";
import Layout from "../components/Layout";

function Transfers() {
    const [fromAccountId, setFromAccountId] = useState("1");
    const [toAccountId, setToAccountId] = useState("2");
    const [amount, setAmount] = useState("");

    const [result, setResult] = useState(null);
    const [loading, setLoading] = useState(false);

    const [message, setMessage] = useState("");
    const [error, setError] = useState("");

    const transferMoney = async (e) => {
        e.preventDefault();

        setLoading(true);
        setMessage("");
        setError("");
        setResult(null);

        try {
            const response = await api.post("/transfer", {
                from_account_id: Number(fromAccountId),
                to_account_id: Number(toAccountId),
                amount: Number(amount),
            });

            setResult(response.data);
            setMessage(
                response.data.message || "Transfer successful."
            );
        } catch (err) {
            setError(
                err.response?.data?.message ||
                "Failed to complete transfer."
            );
        } finally {
            setLoading(false);
        }
    };

    return (
        <Layout>
            <div className="page-header">
                <h2>Transfer Money Safely</h2>

                <p>
                    3.5 — Safely transfer money between accounts using
                    database transactions and consistent row locking.
                </p>
            </div>

            <div className="test-panel">
                <h3>Make Transfer</h3>

                <form onSubmit={transferMoney}>

                    <div className="form-group">
                        <label>From Account ID</label>

                        <input
                            type="number"
                            value={fromAccountId}
                            onChange={(e) =>
                                setFromAccountId(e.target.value)
                            }
                            min="1"
                            required
                        />
                    </div>

                    <div className="form-group">
                        <label>To Account ID</label>

                        <input
                            type="number"
                            value={toAccountId}
                            onChange={(e) =>
                                setToAccountId(e.target.value)
                            }
                            min="1"
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
                        {loading ? "Transferring..." : "Transfer Money"}
                    </button>

                </form>
            </div>

            {result && (
                <div className="test-panel">
                    <h3>Transfer Details</h3>

                    <div className="ticket-info">

                        <div>
                            <span>From Account</span>
                            <strong>
                                {result.from_account.id}
                            </strong>
                        </div>

                        <div>
                            <span>From Balance</span>
                            <strong>
                                {result.from_account.balance}
                            </strong>
                        </div>

                        <div>
                            <span>To Account</span>
                            <strong>
                                {result.to_account.id}
                            </strong>
                        </div>

                        <div>
                            <span>To Balance</span>
                            <strong>
                                {result.to_account.balance}
                            </strong>
                        </div>

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

export default Transfers;