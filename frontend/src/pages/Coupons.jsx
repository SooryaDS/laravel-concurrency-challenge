import { useState } from "react";
import api from "../services/api";
import Layout from "../components/Layout";

function Coupons() {
    const [couponId, setCouponId] = useState("1");
    const [loading, setLoading] = useState(false);

    const [result, setResult] = useState(null);
    const [message, setMessage] = useState("");
    const [error, setError] = useState("");

    const redeemCoupon = async (e) => {
        e.preventDefault();

        setLoading(true);
        setResult(null);
        setMessage("");
        setError("");

        try {
            const response = await api.post(
                `/coupons/${couponId}/redeem`
            );

            setResult(response.data);

            setMessage(
                response.data.message ||
                "Coupon redeemed successfully."
            );
        } catch (err) {
            setError(
                err.response?.data?.message ||
                "Failed to redeem coupon."
            );
        } finally {
            setLoading(false);
        }
    };

    return (
        <Layout>
            <div className="page-header">
                <h2>Coupon Race Condition</h2>
                <p>
                    3.10 — Coupon redemptions remain safe under
                    concurrent requests using database transactions
                    and row locking.
                </p>
            </div>

            <div className="test-panel">
                <h3>Redeem Coupon</h3>

                <form onSubmit={redeemCoupon}>
                    <div className="form-group">
                        <label>Coupon ID</label>

                        <input
                            type="number"
                            value={couponId}
                            onChange={(e) =>
                                setCouponId(e.target.value)
                            }
                            min="1"
                            required
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={loading}
                    >
                        {loading
                            ? "Redeeming..."
                            : "Redeem Coupon"}
                    </button>
                </form>
            </div>

            {result && (
                <div className="test-panel">
                    <h3>Redemption Details</h3>

                    <div className="ticket-info">
                        <div>
                            <span>Coupon ID</span>
                            <strong>{couponId}</strong>
                        </div>

                        <div>
                            <span>Current Uses</span>
                            <strong>{result.uses}</strong>
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

export default Coupons;