import { LegalPage } from '@/components/legal-page';

export default function Cookies() {
    return (
        <LegalPage title="Cookie Policy" updated="October 27, 2026">
            <section>
                <h2>What we use</h2>
                <p>
                    Satsuma only uses cookies that are strictly necessary to
                    run the application. We do not use advertising or
                    analytics trackers.
                </p>
            </section>

            <section>
                <h2>Essential cookies</h2>
                <p>
                    <strong>Session cookie.</strong> Keeps you signed in and
                    remembers your current organization between requests.
                </p>
                <p>
                    <strong>CSRF token.</strong> Protects forms and API
                    requests against cross-site request forgery.
                </p>
                <p>
                    <strong>Remember-me cookie.</strong> Set only if you
                    choose "remember me" at sign-in, so your session survives
                    a browser restart.
                </p>
            </section>

            <section>
                <h2>Third-party cookies</h2>
                <p>
                    We do not embed third-party scripts that set cookies on
                    the dashboard.
                </p>
            </section>

            <section>
                <h2>Managing cookies</h2>
                <p>
                    You can block or delete cookies in your browser settings.
                    Because our cookies are required for sign-in and request
                    security, the service will not work if you disable them.
                </p>
            </section>

            <section>
                <h2>Contact</h2>
                <p>
                    Questions about this policy can be sent to
                    privacy@satsuma.app.
                </p>
            </section>
        </LegalPage>
    );
}
