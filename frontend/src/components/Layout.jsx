import Sidebar from "./Sidebar";

function Layout({ children }) {
    return (
        <div className="app">
            <Sidebar />

            <main className="main-content">
                <header className="topbar">
                    <div>
                        <h1>Concurrency & Reliability</h1>
                        <p>Laravel Backend Challenge</p>
                    </div>
                </header>

                <section className="page-content">
                    {children}
                </section>
            </main>
        </div>
    );
}

export default Layout;