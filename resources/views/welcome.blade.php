<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Service Assessment | Apollos Geofrey</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

    <!-- Header Section -->
    <header class="bg-gradient-to-r from-orange-400 via-red-500 to-pink-500 text-white p-8 text-center shadow-lg">
        <h1 class="text-4xl md:text-5xl font-bold">Notification Service Assessment</h1>
        <p class="mt-2 text-lg md:text-xl">Backend Developer Task Completed by <strong>Apollos Geofrey</strong></p>
    </header>

    <!-- About Section -->
    <section class="max-w-5xl mx-auto mt-10 p-6 bg-white rounded-xl shadow-lg">
        <h2 class="text-2xl font-semibold mb-4 text-blue-600">About the Assessment</h2>
        <p class="text-gray-700 mb-4 text-lg">
            This Laravel-based project demonstrates my skills in building scalable backend systems with priority queuing, status tracking, and API-first design. 
            The assessment focuses on asynchronous processing, message delivery reliability, and integration-ready architecture.
        </p>
        <a href="https://apollosgeofrey.epizy.com" target="_blank" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg shadow transition">
            Visit My Portfolio
        </a>
    </section>

    <!-- Features Section -->
    <section class="max-w-5xl mx-auto mt-10 p-6 bg-gradient-to-r from-green-100 to-green-200 rounded-xl shadow-lg">
        <h2 class="text-2xl font-semibold mb-4 text-green-800">Key Features Implemented</h2>
        <ul class="list-disc list-inside text-gray-800 text-lg space-y-2">
            <li>RESTful Laravel API for bulk SMS and Email notifications</li>
            <li>Priority queue handling with Redis and RabbitMQ</li>
            <li>Delivery status tracking: queued, sent, delivered, discarded</li>
            <li>Automatic retry mechanisms for failed notifications</li>
            <li>Idempotency handling to avoid duplicate deliveries</li>
            <li>Integration-ready mock providers for external services</li>
            <li>Docker Compose deployment with PostgreSQL, Redis, and Laravel app</li>
        </ul>
    </section>

    <!-- Technical Details Section -->
    <section class="max-w-5xl mx-auto mt-10 p-6 bg-gradient-to-r from-purple-100 to-purple-200 rounded-xl shadow-lg">
        <h2 class="text-2xl font-semibold mb-4 text-purple-800">Technical Stack</h2>
        <div class="grid md:grid-cols-2 gap-4 text-gray-800 text-lg">
            <div>
                <h3 class="font-semibold text-purple-700 mb-2">Backend & API</h3>
                <ul class="list-disc list-inside">
                    <li>PHP 8.3, Laravel Framework</li>
                    <li>RESTful API endpoints</li>
                    <li>Integration with mock SMS & Email providers</li>
                </ul>
            </div>
            <div>
                <h3 class="font-semibold text-purple-700 mb-2">Database & Queue</h3>
                <ul class="list-disc list-inside">
                    <li>PostgreSQL for persistent storage</li>
                    <li>RabbitMQ for message queues</li>
                    <li>Redis for caching & idempotency</li>
                </ul>
            </div>
            <div>
                <h3 class="font-semibold text-purple-700 mb-2">DevOps & Testing</h3>
                <ul class="list-disc list-inside">
                    <li>Docker Compose for multi-service deployment</li>
                    <li>Integration tests for end-to-end workflow</li>
                    <li>Postman / Swagger API documentation</li>
                </ul>
            </div>
            <div>
                <h3 class="font-semibold text-purple-700 mb-2">Frontend (Optional)</h3>
                <ul class="list-disc list-inside">
                    <li>Minimal landing page (this page)</li>
                    <li>Responsive Tailwind CSS design</li>
                    <li>Focus is backend / API functionality</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
    <footer class="mt-12 p-6 text-center bg-gray-800 text-white rounded-t-xl">
        <p class="text-lg">Author: Apollos Geofrey | Email: <a href="mailto:apollosgeofrey@gmail.com" class="underline">apollosgeofrey@gmail.com</a></p>
        <p class="mt-2">GitHub: <a href="https://github.com/apollosgeofrey" target="_blank" class="underline">https://github.com/apollosgeofrey</a></p>
        <p class="mt-1 text-sm text-gray-300">© 2026 Apollos Geofrey | Backend Developer Assessment</p>
    </footer>

</body>
</html>