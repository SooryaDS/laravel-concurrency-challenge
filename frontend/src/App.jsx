import { BrowserRouter, Routes, Route } from "react-router-dom";

import Dashboard from "./pages/Dashboard";
import Tickets from "./pages/Tickets";
import Payments from "./pages/Payments";
import Stock from "./pages/Stock";
import Bookings from "./pages/Bookings";
import Transfers from "./pages/Transfers";
import Invoices from "./pages/Invoices";
import LoginCode from "./pages/LoginCode";
import Documents from "./pages/Documents";
import Coupons from "./pages/Coupons";
import Login from "./pages/Login";

function App() {
    return (
        <BrowserRouter>
            <Routes>
                <Route path="/" element={<Dashboard />} />
                <Route path="/tickets" element={<Tickets />} />
                <Route path="/payments" element={<Payments />} />
                <Route path="/stock" element={<Stock />} />
                <Route path="/bookings" element={<Bookings />} />
                <Route path="/transfers" element={<Transfers />} />
                <Route path="/invoices" element={<Invoices />} />
                <Route path="/login-code" element={<LoginCode />} />
                <Route path="/documents" element={<Documents />} />
                <Route path="/coupons" element={<Coupons />} />
                <Route path="/login" element={<Login />} />
            </Routes>
        </BrowserRouter>
    );
}

export default App;