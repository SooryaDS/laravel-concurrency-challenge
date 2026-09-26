import { useState } from "react";
import api from "../services/api";
import Layout from "../components/Layout";

function Tickets() {
    const [ticketId, setTicketId] = useState("1");
    const [ticket, setTicket] = useState(null);

    const [title, setTitle] = useState("");
    const [status, setStatus] = useState("");

    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);
    const [message, setMessage] = useState("");
    const [error, setError] = useState("");

    const loadTicket = async () => {
        setLoading(true);
        setMessage("");
        setError("");

        try {
            const response = await api.get(`/tickets/${ticketId}`);

            setTicket(response.data);
            setTitle(response.data.title);
            setStatus(response.data.status);
        } catch (err) {
            setTicket(null);
            setError(
                err.response?.data?.message ||
                "Failed to load ticket."
            );
        } finally {
            setLoading(false);
        }
    };

    const updateTicket = async (e) => {
        e.preventDefault();

        if (!ticket) {
            return;
        }

        setSaving(true);
        setMessage("");
        setError("");

        try {
            const response = await api.put(`/tickets/${ticket.id}`, {
                title,
                status,
                version: ticket.version,
            });

            setTicket(response.data);
            setTitle(response.data.title);
            setStatus(response.data.status);

            setMessage("Ticket updated successfully.");
        } catch (err) {
            if (err.response?.status === 409) {
                setError(
                    "Conflict: this ticket was already modified. Reload the ticket before updating it."
                );
            } else {
                setError(
                    err.response?.data?.message ||
                    "Failed to update ticket."
                );
            }
        } finally {
            setSaving(false);
        }
    };

    return (
        <Layout>
            <div className="page-header">
                <h2>Prevent Lost Updates</h2>
                <p>
                    3.1 — Optimistic locking prevents stale updates from
                    overwriting newer changes.
                </p>
            </div>

            <div className="test-panel">
                <h3>Load Ticket</h3>

                <div className="form-row">
                    <label>
                        Ticket ID
                        <input
                            type="number"
                            value={ticketId}
                            onChange={(e) => setTicketId(e.target.value)}
                            min="1"
                        />
                    </label>

                    <button
                        type="button"
                        onClick={loadTicket}
                        disabled={loading}
                    >
                        {loading ? "Loading..." : "Load Ticket"}
                    </button>
                </div>
            </div>

            {ticket && (
                <div className="test-panel">
                    <div className="ticket-info">
                        <div>
                            <span>Ticket ID</span>
                            <strong>{ticket.id}</strong>
                        </div>

                        <div>
                            <span>Current Version</span>
                            <strong>{ticket.version}</strong>
                        </div>
                    </div>

                    <form onSubmit={updateTicket}>
                        <div className="form-group">
                            <label>Title</label>
                            <input
                                type="text"
                                value={title}
                                onChange={(e) => setTitle(e.target.value)}
                                required
                            />
                        </div>

                        <div className="form-group">
                            <label>Status</label>
                            <select
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                            >
                                <option value="open">Open</option>
                                <option value="pending">Pending</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>

                        <button
                            type="submit"
                            disabled={saving}
                        >
                            {saving ? "Saving..." : "Update Ticket"}
                        </button>
                    </form>
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

export default Tickets;