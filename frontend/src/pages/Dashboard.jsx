import { Link } from "react-router-dom";
import Layout from "../components/Layout";

function Dashboard() {
    const challenges = [
        {
            number: "3.1",
            title: "Prevent Lost Updates",
            description: "Optimistic locking and conflict handling.",
            path: "/tickets",
        },
        {
            number: "3.2",
            title: "Duplicate Payments",
            description: "Idempotent payment requests.",
            path: "/payments",
        },
        {
            number: "3.3",
            title: "Stock Reservation",
            description: "Safe inventory handling under concurrency.",
            path: "/stock",
        },
        {
            number: "3.4",
            title: "Double Booking",
            description: "Prevent overlapping room bookings.",
            path: "/bookings",
        },
        {
            number: "3.5",
            title: "Money Transfer",
            description: "Atomic financial transactions.",
            path: "/transfers",
        },
        {
            number: "3.6",
            title: "Queue Reliability",
            description: "Prevent duplicate invoice creation.",
            path: "/invoices",
        },
        {
            number: "3.7",
            title: "Rate Limiting",
            description: "Limit login-code requests.",
            path: "/login-code",
        },
        {
            number: "3.8",
            title: "Deadlock Handling",
            description: "Safe database transaction handling.",
            path: "/transfers",
        },
        {
            number: "3.9",
            title: "Secure Downloads",
            description: "Owner-only document access.",
            path: "/documents",
        },
        {
            number: "3.10",
            title: "Coupon Race Condition",
            description: "Concurrency-safe coupon redemption.",
            path: "/coupons",
        },
    ];

    return (
        <Layout>
            <div className="dashboard-header">
                <div>
                    <h2>Challenge Dashboard</h2>
                    <p>
                        Test and demonstrate the Laravel backend scenarios.
                    </p>
                </div>

                <div className="status-badge">
                    Backend Connected
                </div>
            </div>

            <div className="challenge-grid">
                {challenges.map((challenge) => (
                    <Link
                        key={challenge.number}
                        to={challenge.path}
                        className="challenge-card"
                    >
                        <span className="challenge-number">
                            {challenge.number}
                        </span>

                        <h3>{challenge.title}</h3>

                        <p>{challenge.description}</p>

                        <span className="open-link">
                            Open →
                        </span>
                    </Link>
                ))}
            </div>
        </Layout>
    );
}

export default Dashboard;