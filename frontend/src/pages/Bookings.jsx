import { useState } from "react";
import api from "../services/api";
import Layout from "../components/Layout";

function Bookings() {
    const [roomId, setRoomId] = useState("1");
    const [startTime, setStartTime] = useState("");
    const [endTime, setEndTime] = useState("");

    const [booking, setBooking] = useState(null);
    const [loading, setLoading] = useState(false);

    const [message, setMessage] = useState("");
    const [error, setError] = useState("");

    const bookRoom = async (e) => {
        e.preventDefault();

        setLoading(true);
        setMessage("");
        setError("");
        setBooking(null);

        try {
            const response = await api.post(
                `/rooms/${roomId}/book`,
                {
                    start_time: startTime,
                    end_time: endTime,
                }
            );

            setBooking(response.data.booking);
            setMessage(
                response.data.message || "Room booked successfully."
            );
        } catch (err) {
            if (err.response?.status === 409) {
                setError(
                    "This room is already booked for the selected time."
                );
            } else {
                setError(
                    err.response?.data?.message ||
                    "Failed to book the room."
                );
            }
        } finally {
            setLoading(false);
        }
    };

    return (
        <Layout>
            <div className="page-header">
                <h2>Prevent Double Booking</h2>

                <p>
                    3.4 — Prevent overlapping room bookings under
                    concurrent requests.
                </p>
            </div>

            <div className="test-panel">
                <h3>Book Meeting Room</h3>

                <form onSubmit={bookRoom}>

                    <div className="form-group">
                        <label>Room ID</label>

                        <input
                            type="number"
                            value={roomId}
                            onChange={(e) => setRoomId(e.target.value)}
                            min="1"
                            required
                        />
                    </div>

                    <div className="form-group">
                        <label>Start Time</label>

                        <input
                            type="datetime-local"
                            value={startTime}
                            onChange={(e) => setStartTime(e.target.value)}
                            required
                        />
                    </div>

                    <div className="form-group">
                        <label>End Time</label>

                        <input
                            type="datetime-local"
                            value={endTime}
                            onChange={(e) => setEndTime(e.target.value)}
                            required
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={loading}
                    >
                        {loading ? "Booking..." : "Book Room"}
                    </button>

                </form>
            </div>

            {booking && (
                <div className="test-panel">
                    <h3>Booking Details</h3>

                    <div className="ticket-info">

                        <div>
                            <span>Booking ID</span>
                            <strong>{booking.id}</strong>
                        </div>

                        <div>
                            <span>Room ID</span>
                            <strong>{booking.room_id}</strong>
                        </div>

                        <div>
                            <span>Start Time</span>
                            <strong>{booking.start_time}</strong>
                        </div>

                        <div>
                            <span>End Time</span>
                            <strong>{booking.end_time}</strong>
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

export default Bookings;