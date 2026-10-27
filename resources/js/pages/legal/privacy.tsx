import { LegalPage } from '@/components/legal-page';

export default function Privacy() {
    return (
        <LegalPage title="Privacy Policy" updated="October 27, 2026">
            <section>
                <h2>Overview</h2>
                <p>
                    Satsuma is a service for managing WordPress sites. This
                    policy describes what information we collect when you use
                    the service, why we collect it, and how it is handled.
                </p>
            </section>

            <section>
                <h2>Information we collect</h2>
                <p>
                    <strong>Account information.</strong> When you register we
                    store your name, email address and a hashed password, plus
                    the organizations you belong to and their members.
                </p>
                <p>
                    <strong>Site data.</strong> For each site you connect, the
                    Satsuma agent reports technical inventory: the site URL,
                    WordPress and PHP versions, and the installed plugins and
                    themes with their versions. Scans only run when you
                    request them or on a schedule you configure.
                </p>
                <p>
                    <strong>Usage data.</strong> We store operational data
                    needed to run the service — connection status, scan
                    timestamps and notification history.
                </p>
            </section>

            <section>
                <h2>How we use it</h2>
                <p>
                    Collected data is used solely to provide the service:
                    displaying your sites and their status, alerting you to
                    updates and vulnerabilities, and delivering notifications
                    you have enabled. We do not sell your data.
                </p>
            </section>

            <section>
                <h2>Third-party services</h2>
                <p>
                    Email notifications are delivered through our email
                    provider (Resend). Vulnerability lookups use the Wordfence
                    Intelligence feed, which receives software identifiers —
                    not your site URLs or personal data.
                </p>
            </section>

            <section>
                <h2>Retention and deletion</h2>
                <p>
                    Site inventory and notification data is retained while
                    your account is active. Removing a site deletes its stored
                    inventory; deleting your account removes your personal
                    data.
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
