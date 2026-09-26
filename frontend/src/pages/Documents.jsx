import { useState } from "react";
import api from "../services/api";
import Layout from "../components/Layout";

function Documents() {
    const [documentId, setDocumentId] = useState("");
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState("");
    const [error, setError] = useState("");

    const downloadDocument = async (e) => {
        e.preventDefault();

        setLoading(true);
        setMessage("");
        setError("");

        try {
            const response = await api.get(
                `/documents/${documentId}/download`,
                {
                    responseType: "blob",
                }
            );

            const url = window.URL.createObjectURL(
                new Blob([response.data])
            );

            const link = document.createElement("a");
            link.href = url;
            link.setAttribute("download", `document-${documentId}`);
            document.body.appendChild(link);
            link.click();

            link.remove();
            window.URL.revokeObjectURL(url);

            setMessage("Document downloaded successfully.");
        } catch (err) {
            if (err.response?.status === 401) {
                setError("You must be authenticated to download this document.");
            } else if (err.response?.status === 403) {
                setError("You are not authorized to download this document.");
            } else if (err.response?.status === 404) {
                setError("Document or file not found.");
            } else {
                setError("Failed to download document.");
            }
        } finally {
            setLoading(false);
        }
    };

    return (
        <Layout>
            <div className="page-header">
                <h2>Secure File Download</h2>
                <p>
                    3.9 — Documents can only be downloaded by authenticated
                    users who are authorized to access them.
                </p>
            </div>

            <div className="test-panel">
                <h3>Download Document</h3>

                <form onSubmit={downloadDocument}>
                    <div className="form-group">
                        <label>Document ID</label>

                        <input
                            type="number"
                            value={documentId}
                            onChange={(e) => setDocumentId(e.target.value)}
                            min="1"
                            placeholder="e.g. 1"
                            required
                        />
                    </div>

                    <button type="submit" disabled={loading}>
                        {loading ? "Downloading..." : "Download Document"}
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

export default Documents;