<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Service Assessment | Apollos Geofrey</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

    <header class="bg-gradient-to-r from-orange-400 via-red-500 to-pink-500 text-white p-8 text-center shadow-lg">
        <h1 class="text-4xl md:text-5xl font-bold">Notification Service Assessment</h1>
        <p class="mt-2 text-lg md:text-xl">Backend Developer Task by <strong>Apollos Geofrey</strong></p>
    </header>

    <section class="max-w-5xl mx-auto mt-10 p-6 bg-white rounded-xl shadow-lg">
        <h2 class="text-2xl font-semibold mb-4 text-blue-600">About the Project</h2>
        <p class="text-gray-700 mb-4 text-lg">
            A Laravel microservice for bulk SMS and Email notifications with priority queuing,
            delivery status tracking, idempotency, and automatic retries.
        </p>
        <div class="flex flex-wrap gap-3">
            <a href="https://github.com/apollosgeofrey" target="_blank" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg shadow transition">
                GitHub
            </a>
        </div>
    </section>

    <section class="max-w-5xl mx-auto mt-10 p-6 bg-gradient-to-r from-green-100 to-green-200 rounded-xl shadow-lg">
        <h2 class="text-2xl font-semibold mb-4 text-green-800">Key Features</h2>
        <ul class="list-disc list-inside text-gray-800 text-lg space-y-2">
            <li>RESTful API for bulk SMS and Email notifications</li>
            <li>Priority queues — critical traffic processed before marketing</li>
            <li>Status tracking: queued, sent, delivered, discarded</li>
            <li>Automatic retries with exponential backoff</li>
            <li>Idempotency via <code class="bg-white px-1 rounded">Idempotency-Key</code> header + Redis</li>
            <li>Mock SMS and Email providers</li>
            <li>Docker Compose — one command startup</li>
            <li>21 automated integration tests</li>
        </ul>
    </section>

    <section class="max-w-5xl mx-auto mt-10 p-6 bg-gradient-to-r from-purple-100 to-purple-200 rounded-xl shadow-lg">
        <h2 class="text-2xl font-semibold mb-4 text-purple-800">Technical Stack</h2>
        <div class="grid md:grid-cols-2 gap-4 text-gray-800 text-lg">
            <div>
                <h3 class="font-semibold text-purple-700 mb-2">Backend & API</h3>
                <ul class="list-disc list-inside">
                    <li>PHP 8.3, Laravel 13</li>
                    <li>3 REST endpoints under <code class="bg-white px-1 rounded">/api/v1</code></li>
                    <li>Postman API collection</li>
                </ul>
            </div>
            <div>
                <h3 class="font-semibold text-purple-700 mb-2">Data & Queues</h3>
                <ul class="list-disc list-inside">
                    <li>PostgreSQL — persistent storage</li>
                    <li>Redis — priority queues + idempotency cache</li>
                    <li>RabbitMQ — included in Docker stack</li>
                </ul>
            </div>
            <div>
                <h3 class="font-semibold text-purple-700 mb-2">DevOps & Testing</h3>
                <ul class="list-disc list-inside">
                    <li>Docker Compose with API + worker</li>
                    <li>Integration tests (PHPUnit)</li>
                    <li>Health endpoint at <code class="bg-white px-1 rounded">/up</code></li>
                </ul>
            </div>
            <div>
                <h3 class="font-semibold text-purple-700 mb-2">Quick Start</h3>
                <ul class="list-disc list-inside">
                    <li><code class="bg-white px-1 rounded text-sm">docker compose up --build</code></li>
                    <li>API: <code class="bg-white px-1 rounded text-sm">localhost:8000</code></li>
                    <li>See README.md for full guide</li>
                </ul>
            </div>
        </div>
    </section>

    <footer class="mt-12 p-6 text-center bg-gray-800 text-white rounded-t-xl">
        <p class="text-lg">Author: Apollos Geofrey | Email: <a href="mailto:apollosgeofrey@gmail.com" class="underline">apollosgeofrey@gmail.com</a></p>
        <p class="mt-2">GitHub: <a href="https://github.com/apollosgeofrey" target="_blank" class="underline">https://github.com/apollosgeofrey</a></p>
        <p class="mt-1 text-sm text-gray-300">&copy; 2026 Apollos Geofrey | Backend Developer Assessment</p>
    </footer>

</body>
</html>
