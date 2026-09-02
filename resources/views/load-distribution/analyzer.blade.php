<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Load Distribution Analyzer</title>

    <script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="bg-gray-950 text-white min-h-screen">

<div class="max-w-7xl mx-auto px-6 py-8">

    <!-- Header -->

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">

        <div>

            <h1 class="text-3xl font-bold">
                Load Distribution Analyzer
            </h1>

            <p class="text-gray-400 mt-2">
                Analyze how evenly traffic is distributed across your servers.
            </p>

        </div>

        <div class="flex gap-3">

            <a href="{{ route('load-distribution.weights') }}"
               class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500">
                Manage Weights
            </a>

            <a href="{{ url('/metrics') }}"
               class="px-4 py-2 rounded-lg bg-gray-800 hover:bg-gray-700">
                Metrics
            </a>

        </div>

    </div>


    <!-- Controls -->

    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 mb-8">

        <div class="flex flex-col md:flex-row md:items-end gap-4">

            <div>

                <label class="block text-sm text-gray-400 mb-2">
                    Analysis Period
                </label>

                <select
                    id="period"
                    class="bg-gray-800 border border-gray-700 rounded-lg px-4 py-3">

                    <option value="1">
                        Last 24 Hours
                    </option>

                    <option value="7">
                        Last 7 Days
                    </option>

                    <option value="30">
                        Last 30 Days
                    </option>

                </select>

            </div>

            <button
                onclick="analyzeDistribution()"
                class="bg-indigo-600 hover:bg-indigo-500 px-6 py-3 rounded-lg font-medium">

                Analyze Distribution

            </button>

        </div>

    </div>


    <!-- Loading -->

    <div id="loading"
         class="hidden text-center py-10 text-gray-400">

        Analyzing traffic distribution...

    </div>


    <!-- Results -->

    <div id="results"
         class="hidden">

        <!-- Summary Cards -->

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-5 mb-8">

            <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">

                <p class="text-gray-400 text-sm">
                    Total Requests
                </p>

                <p id="totalRequests"
                   class="text-2xl font-bold mt-2">
                    0
                </p>

            </div>


            <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">

                <p class="text-gray-400 text-sm">
                    Online Servers
                </p>

                <p id="onlineServers"
                   class="text-2xl font-bold mt-2">
                    0
                </p>

            </div>


            <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">

                <p class="text-gray-400 text-sm">
                    Offline Servers
                </p>

                <p id="offlineServers"
                   class="text-2xl font-bold mt-2">
                    0
                </p>

            </div>


            <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">

                <p class="text-gray-400 text-sm">
                    Average Deviation
                </p>

                <p id="averageDeviation"
                   class="text-2xl font-bold mt-2">
                    0%
                </p>

            </div>


            <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">

                <p class="text-gray-400 text-sm">
                    Balance Score
                </p>

                <p id="balanceScore"
                   class="text-2xl font-bold mt-2">
                    0
                </p>

                <p id="balanceStatus"
                   class="text-xs text-gray-400 mt-1">
                </p>

            </div>

        </div>


        <!-- Most / Least Utilized -->

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">

            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">

                <p class="text-gray-400 text-sm">
                    Most Utilized Server
                </p>

                <h3 id="mostUtilized"
                    class="text-2xl font-bold mt-2">
                    -
                </h3>

                <p id="mostUtilizedPercentage"
                   class="text-indigo-400 mt-1">
                </p>

            </div>


            <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">

                <p class="text-gray-400 text-sm">
                    Least Utilized Server
                </p>

                <h3 id="leastUtilized"
                    class="text-2xl font-bold mt-2">
                    -
                </h3>

                <p id="leastUtilizedPercentage"
                   class="text-indigo-400 mt-1">
                </p>

            </div>

        </div>


        <!-- Warnings -->

        <div id="warningsContainer"
             class="hidden bg-yellow-950 border border-yellow-800 rounded-xl p-6 mb-8">

            <h3 class="font-semibold text-yellow-300 mb-3">
                Distribution Warnings
            </h3>

            <ul id="warnings"
                class="space-y-2 text-yellow-200 text-sm">
            </ul>

        </div>


        <!-- Distribution Table -->

        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">

            <div class="mb-6">

                <h2 class="text-xl font-semibold">
                    Server Distribution
                </h2>

                <p class="text-gray-400 text-sm mt-1">
                    Expected traffic is calculated from the configured server weights.
                </p>

            </div>


            <div class="overflow-x-auto">

                <table class="w-full">

                    <thead>

                    <tr class="border-b border-gray-700 text-gray-400 text-sm">

                        <th class="text-left py-3">
                            Server
                        </th>

                        <th class="text-left py-3">
                            Status
                        </th>

                        <th class="text-left py-3">
                            Weight
                        </th>

                        <th class="text-left py-3">
                            Requests
                        </th>

                        <th class="text-left py-3">
                            Expected
                        </th>

                        <th class="text-left py-3">
                            Actual
                        </th>

                        <th class="text-left py-3">
                            Deviation
                        </th>

                        <th class="text-left py-3">
                            Avg Response
                        </th>

                    </tr>

                    </thead>

                    <tbody id="distributionTable"></tbody>

                </table>

            </div>

        </div>

    </div>

</div>


<script>

async function analyzeDistribution()
{
    const period =
        document.getElementById('period').value;

    const loading =
        document.getElementById('loading');

    const results =
        document.getElementById('results');

    loading.classList.remove('hidden');

    results.classList.add('hidden');

    try {

        const response = await fetch(
            `{{ route('load-distribution.analyze') }}?days=${period}`,
            {
                headers: {
                    'Accept': 'application/json'
                }
            }
        );

        const data = await response.json();

        if (!response.ok || !data.success) {

            throw new Error(
                data.message || 'Unable to analyze distribution.'
            );

        }

        renderResults(data);

        results.classList.remove('hidden');

    } catch (error) {

        alert(error.message);

    } finally {

        loading.classList.add('hidden');

    }
}


function renderResults(data)
{
    const summary = data.summary;

    document.getElementById('totalRequests')
        .textContent =
        Number(summary.total_requests).toLocaleString();

    document.getElementById('onlineServers')
        .textContent =
        summary.online_servers;

    document.getElementById('offlineServers')
        .textContent =
        summary.offline_servers;

    document.getElementById('averageDeviation')
        .textContent =
        `${summary.average_deviation}%`;

    document.getElementById('balanceScore')
        .textContent =
        `${summary.balance_score}/100`;

    document.getElementById('balanceStatus')
        .textContent =
        summary.balance_status;


    if (data.most_utilized) {

        document.getElementById('mostUtilized')
            .textContent =
            data.most_utilized.name;

        document.getElementById('mostUtilizedPercentage')
            .textContent =
            `${data.most_utilized.actual_percentage}% traffic`;

    } else {

        document.getElementById('mostUtilized')
            .textContent = '-';

        document.getElementById('mostUtilizedPercentage')
            .textContent = '';

    }


    if (data.least_utilized) {

        document.getElementById('leastUtilized')
            .textContent =
            data.least_utilized.name;

        document.getElementById('leastUtilizedPercentage')
            .textContent =
            `${data.least_utilized.actual_percentage}% traffic`;

    } else {

        document.getElementById('leastUtilized')
            .textContent = '-';

        document.getElementById('leastUtilizedPercentage')
            .textContent = '';

    }


    renderWarnings(data.warnings);

    renderDistribution(data.servers);
}


function renderWarnings(warnings)
{
    const container =
        document.getElementById('warningsContainer');

    const list =
        document.getElementById('warnings');

    list.innerHTML = '';

    if (!warnings || warnings.length === 0) {

        container.classList.add('hidden');

        return;
    }

    container.classList.remove('hidden');

    warnings.forEach(warning => {

        const item =
            document.createElement('li');

        item.textContent = `• ${warning}`;

        list.appendChild(item);

    });
}


function renderDistribution(servers)
{
    const table =
        document.getElementById('distributionTable');

    table.innerHTML = '';

    servers.forEach(server => {

        const row =
            document.createElement('tr');

        row.className =
            'border-b border-gray-800 text-sm';

        const deviationClass =
            server.deviation >= 15
                ? 'text-red-400'
                : server.deviation >= 5
                    ? 'text-yellow-400'
                    : 'text-green-400';


        row.innerHTML = `

            <td class="py-4 font-medium">
                ${server.name}
            </td>

            <td class="py-4">

                ${
                    server.is_online
                        ? '<span class="text-green-400">Online</span>'
                        : '<span class="text-red-400">Offline</span>'
                }

            </td>

            <td class="py-4">
                ${server.weight}
            </td>

            <td class="py-4">
                ${Number(server.requests).toLocaleString()}
            </td>

            <td class="py-4">
                ${server.expected_percentage}%
            </td>

            <td class="py-4">

                <div class="min-w-[140px]">

                    <div class="flex justify-between mb-1">

                        <span>
                            ${server.actual_percentage}%
                        </span>

                    </div>

                    <div class="h-2 bg-gray-800 rounded-full overflow-hidden">

                        <div
                            class="h-full bg-indigo-500"
                            style="width:${Math.min(
                                server.actual_percentage,
                                100
                            )}%">
                        </div>

                    </div>

                </div>

            </td>

            <td class="py-4 ${deviationClass}">
                ${server.deviation}%
            </td>

            <td class="py-4">
                ${server.avg_response_time} ms
            </td>

        `;

        table.appendChild(row);

    });
}


// Automatically analyze when page opens.
analyzeDistribution();

</script>

</body>

</html>