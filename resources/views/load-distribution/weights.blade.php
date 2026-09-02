<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token"
          content="{{ csrf_token() }}">

    <title>Dynamic Server Weights</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-950 text-white min-h-screen">

<div class="max-w-7xl mx-auto px-6 py-8">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">

        <div>
            <h1 class="text-3xl font-bold">
                Dynamic Server Weights
            </h1>

            <p class="text-gray-400 mt-2">
                Configure server weights and rebalance Weighted Round Robin traffic.
            </p>
        </div>

        <div class="flex gap-3">

            <a href="{{ url('/dashboard') }}"
               class="px-4 py-2 rounded-lg bg-gray-800 hover:bg-gray-700">
                Dashboard
            </a>

            <a href="{{ route('load-distribution.analyzer') }}"
               class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500">
                Distribution Analyzer
            </a>

        </div>

    </div>

    <div id="message"
         class="hidden mb-6 rounded-lg px-4 py-3"></div>

    <!-- Weight Cards -->

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

        @foreach($servers as $server)

            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">

                <div class="flex justify-between items-start mb-5">

                    <div>
                        <h2 class="text-xl font-semibold">
                            {{ $server->name }}
                        </h2>

                        <p class="text-sm text-gray-500 mt-1">
                            {{ $server->ip_address }}:{{ $server->port }}
                        </p>
                    </div>

                    @if($server->is_online)

                        <span class="px-3 py-1 rounded-full text-xs bg-green-900 text-green-300">
                            Online
                        </span>

                    @else

                        <span class="px-3 py-1 rounded-full text-xs bg-red-900 text-red-300">
                            Offline
                        </span>

                    @endif

                </div>

                <div class="mb-5">

                    <label class="block text-sm text-gray-400 mb-2">
                        Server Weight
                    </label>

                    <input
                        type="number"
                        min="1"
                        max="100"
                        value="{{ $server->weight }}"
                        data-server-id="{{ $server->id }}"
                        class="weight-input w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 outline-none"
                    >

                    <p class="text-xs text-gray-500 mt-2">
                        Allowed range: 1–100
                    </p>

                </div>

                <button
                    onclick="updateWeight({{ $server->id }})"
                    class="w-full bg-indigo-600 hover:bg-indigo-500 py-2.5 rounded-lg font-medium transition">
                    Update Weight
                </button>

                <div class="mt-5 pt-5 border-t border-gray-800">

                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-gray-400">
                            Current Requests
                        </span>

                        <span>
                            {{ number_format($server->request_count) }}
                        </span>
                    </div>

                    <div class="flex justify-between text-sm">

                        <span class="text-gray-400">
                            Active Connections
                        </span>

                        <span>
                            {{ $server->active_connections }}
                        </span>

                    </div>

                </div>

            </div>

        @endforeach

    </div>

    <!-- Weighted Traffic Simulation -->

    <div class="mt-10 bg-gray-900 border border-gray-800 rounded-2xl p-6">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">

            <div>
                <h2 class="text-xl font-semibold">
                    Weighted Traffic Simulation
                </h2>

                <p class="text-gray-400 text-sm mt-1">
                    Test how requests are distributed using the current server weights.
                </p>
            </div>

            <div class="flex gap-3">

                <input
                    id="simulationRequests"
                    type="number"
                    min="1"
                    max="10000"
                    value="100"
                    class="bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 w-32"
                >

                <button
                    onclick="simulateWeightedTraffic()"
                    class="bg-green-600 hover:bg-green-500 px-5 py-2 rounded-lg font-medium">
                    Simulate
                </button>

            </div>

        </div>

        <div id="simulationResult" class="hidden">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

                <div class="bg-gray-800 rounded-xl p-4">
                    <p class="text-gray-400 text-sm">
                        Total Requests
                    </p>
                    <p id="totalRequests"
                       class="text-2xl font-bold mt-1">
                        0
                    </p>
                </div>

                <div class="bg-gray-800 rounded-xl p-4">
                    <p class="text-gray-400 text-sm">
                        Total Weight
                    </p>
                    <p id="totalWeight"
                       class="text-2xl font-bold mt-1">
                        0
                    </p>
                </div>

                <div class="bg-gray-800 rounded-xl p-4">
                    <p class="text-gray-400 text-sm">
                        Routing Algorithm
                    </p>
                    <p class="text-2xl font-bold mt-1">
                        Weighted Round Robin
                    </p>
                </div>

            </div>

            <div class="overflow-x-auto">

                <table class="w-full text-sm">

                    <thead>
                    <tr class="border-b border-gray-700 text-gray-400">
                        <th class="text-left py-3">Server</th>
                        <th class="text-left py-3">Weight</th>
                        <th class="text-left py-3">Requests</th>
                        <th class="text-left py-3">Expected %</th>
                        <th class="text-left py-3">Actual %</th>
                        <th class="text-left py-3">Deviation</th>
                    </tr>
                    </thead>

                    <tbody id="simulationTable"></tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<script>

const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    .getAttribute('content');


function showMessage(message, success = true)
{
    const element = document.getElementById('message');

    element.textContent = message;

    element.classList.remove(
        'hidden',
        'bg-green-900',
        'text-green-300',
        'bg-red-900',
        'text-red-300'
    );

    if (success) {

        element.classList.add(
            'bg-green-900',
            'text-green-300'
        );

    } else {

        element.classList.add(
            'bg-red-900',
            'text-red-300'
        );

    }

    setTimeout(() => {
        element.classList.add('hidden');
    }, 4000);
}


async function updateWeight(serverId)
{
    const input = document.querySelector(
        `.weight-input[data-server-id="${serverId}"]`
    );

    const weight = parseInt(input.value);

    if (!weight || weight < 1 || weight > 100) {

        showMessage(
            'Weight must be between 1 and 100.',
            false
        );

        return;
    }

    try {

        const response = await fetch(
            `/load-distribution/weights/${serverId}`,
            {
                method: 'POST',

                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },

                body: JSON.stringify({
                    weight: weight
                })
            }
        );

        const data = await response.json();

        if (!response.ok) {

            throw new Error(
                data.message || 'Unable to update weight.'
            );

        }

        showMessage(data.message);

    } catch (error) {

        showMessage(
            error.message,
            false
        );

    }
}


async function simulateWeightedTraffic()
{
    const requests = parseInt(
        document.getElementById('simulationRequests').value
    );

    if (!requests || requests < 1 || requests > 10000) {

        showMessage(
            'Requests must be between 1 and 10,000.',
            false
        );

        return;
    }

    try {

        const response = await fetch(
            '{{ route('load-distribution.simulate-weighted') }}',
            {
                method: 'POST',

                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },

                body: JSON.stringify({
                    requests: requests
                })
            }
        );

        const data = await response.json();

        if (!response.ok || !data.success) {

            throw new Error(
                data.message || 'Simulation failed.'
            );

        }

        document
            .getElementById('simulationResult')
            .classList.remove('hidden');

        document
            .getElementById('totalRequests')
            .textContent = data.total_requests;

        document
            .getElementById('totalWeight')
            .textContent = data.total_weight;

        const table =
            document.getElementById('simulationTable');

        table.innerHTML = '';

        data.servers.forEach(server => {

            const row = document.createElement('tr');

            row.className =
                'border-b border-gray-800';

            row.innerHTML = `
                <td class="py-4 font-medium">
                    ${server.name}
                </td>

                <td class="py-4">
                    ${server.weight}
                </td>

                <td class="py-4">
                    ${server.requests}
                </td>

                <td class="py-4">
                    ${server.expected_percentage}%
                </td>

                <td class="py-4">
                    ${server.actual_percentage}%
                </td>

                <td class="py-4 ${
                    server.deviation >= 5
                        ? 'text-yellow-400'
                        : 'text-green-400'
                }">
                    ${server.deviation}%
                </td>
            `;

            table.appendChild(row);

        });

    } catch (error) {

        showMessage(
            error.message,
            false
        );

    }
}

</script>

</body>
</html>