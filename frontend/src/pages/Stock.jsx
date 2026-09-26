import { useState } from "react";
import api from "../services/api";
import Layout from "../components/Layout";

function Stock() {
    const [productId, setProductId] = useState("1");
    const [quantity, setQuantity] = useState("1");

    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState("");
    const [error, setError] = useState("");

    const reserveStock = async (e) => {
        e.preventDefault();

        setLoading(true);
        setMessage("");
        setError("");

        try {
            const response = await api.post(
                `/products/${productId}/reserve`,
                {
                    quantity: Number(quantity),
                }
            );

            setMessage(
                response.data.message || "Stock reserved successfully."
            );
        } catch (err) {
            setError(
                err.response?.data?.message ||
                "Failed to reserve stock."
            );
        } finally {
            setLoading(false);
        }
    };

    return (
        <Layout>
            <div className="page-header">
                <h2>Stock Reservation</h2>

                <p>
                    3.3 — Safe inventory handling under concurrency.
                </p>
            </div>

            <div className="test-panel">
                <h3>Reserve Product</h3>

                <form onSubmit={reserveStock}>
                    <div className="form-group">
                        <label>Product ID</label>

                        <input
                            type="number"
                            value={productId}
                            onChange={(e) => setProductId(e.target.value)}
                            min="1"
                            required
                        />
                    </div>

                    <div className="form-group">
                        <label>Quantity</label>

                        <input
                            type="number"
                            value={quantity}
                            onChange={(e) => setQuantity(e.target.value)}
                            min="1"
                            required
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={loading}
                    >
                        {loading ? "Reserving..." : "Reserve Stock"}
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

export default Stock;