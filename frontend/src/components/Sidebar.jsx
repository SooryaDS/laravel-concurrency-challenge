import { NavLink, useNavigate } from "react-router-dom";

function Sidebar() {
    const navigate = useNavigate();

    const links = [
        { name: "Dashboard", path: "/" },
        { name: "Tickets", path: "/tickets" },
        { name: "Payments", path: "/payments" },
        { name: "Stock", path: "/stock" },
        { name: "Room Bookings", path: "/bookings" },
        { name: "Money Transfer", path: "/transfers" },
        { name: "Invoices", path: "/invoices" },
        { name: "Login Code", path: "/login-code" },
        { name: "Documents", path: "/documents" },
        { name: "Coupons", path: "/coupons" },
    ];

    const logout = () => {
        localStorage.removeItem("auth_token");
        navigate("/login");
    };

    return (
        <aside className="sidebar">
            <div className="sidebar-logo">
                <h2>Laravel Challenge</h2>
                <span>Backend Scenarios</span>
            </div>

            <nav className="sidebar-nav">
                {links.map((link) => (
                    <NavLink
                        key={link.path}
                        to={link.path}
                        className={({ isActive }) =>
                            isActive ? "nav-link active" : "nav-link"
                        }
                    >
                        {link.name}
                    </NavLink>
                ))}

                <button
                    type="button"
                    className="nav-link logout-button"
                    onClick={logout}
                >
                    Logout
                </button>
            </nav>
        </aside>
    );
}

export default Sidebar;