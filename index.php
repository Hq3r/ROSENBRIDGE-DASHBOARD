<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$offset = 0;
$limit = 450;

$allowed_origin = '*';

header("Access-Control-Allow-Origin: $allowed_origin");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');
header('Content-Type: text/html');

$ch = curl_init('https://app.rosen.tech/api/v1/events?offset=' . $offset . '&limit=' . $limit);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

if ($response === false) {
    echo 'Curl error: ' . curl_error($ch);
    exit();
}

$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo 'JSON decoding error: ' . json_last_error_msg();
    exit();
}

// Sort data by ID in ascending order
usort($data['items'], function ($a, $b) {
    return $a['id'] <=> $b['id'];
});

// Variables to store total amounts
$totalBridgeEvents = count($data['items']); // Total number of events

$totalERGSent = 0;
$totalRSNSent = 0;
$totalrsADASent = 0;

$totalRSERGSent = 0;
$totalADASent = 0;
$totalrsRSNSent = 0;

foreach ($data['items'] as $event) {
    switch ($event['lockToken']['name']) {
        case 'ERG':
            if ($event['fromChain'] === 'ergo' && $event['toChain'] === 'cardano') {
                $totalERGSent += $event['amount'] / (10 ** $event['lockToken']['decimals']);
            }
            break;
        case 'rsERG':
            if ($event['fromChain'] === 'cardano' && $event['toChain'] === 'ergo') {
                $totalRSERGSent += $event['amount'] / (10 ** $event['lockToken']['decimals']);
            }
            break;
        case 'ADA':
            if ($event['fromChain'] === 'cardano' && $event['toChain'] === 'ergo') {
                $totalADASent += $event['amount'] / (10 ** $event['lockToken']['decimals']);
            }
            break;
        case 'rsADA':
            if ($event['fromChain'] === 'ergo' && $event['toChain'] === 'cardano') {
                $totalrsADASent += $event['amount'] / (10 ** $event['lockToken']['decimals']);
            }
            break;
        case 'RSN':
            if ($event['fromChain'] === 'ergo' && $event['toChain'] === 'cardano') {
                $totalRSNSent += $event['amount'] / (10 ** $event['lockToken']['decimals']);
            }
            break;
        case 'rsRSN':
            if ($event['fromChain'] === 'cardano' && $event['toChain'] === 'ergo') {
                $totalrsRSNSent += $event['amount'] / (10 ** $event['lockToken']['decimals']);
            }
            break;
        default:
            // Do nothing for other tokens
            break;
    }
}




// Counting successful and processing events
$totalSuccessfulEvents = 0;
$totalProcessingEvents = 0;

foreach ($data['items'] as $event) {
    if ($event['status'] === 'successful') {
        $totalSuccessfulEvents++;
    } elseif ($event['status'] === 'processing') {
        $totalProcessingEvents++;
    }
}
// Variables to store sender statistics for each token
$sendersERG = [];
$sendersADA = [];
$sendersRSN = [];
$sendersrsADA = [];
$sendersrsERG = [];
$sendersrsRSN = [];

foreach ($data['items'] as $event) {
    $sender = $event['fromAddress'];
    $receiver = $event['toAddress'];
    $amount = $event['amount'] / (10 ** $event['lockToken']['decimals']);
    $tokenName = $event['lockToken']['name'];

    // Store sender statistics for each token category
    switch ($tokenName) {
        case 'ERG':
            $sendersERG[$sender] = $amount;
            break;
        case 'ADA':
            $sendersADA[$sender] = $amount;
            break;
        case 'RSN':
            $sendersRSN[$sender] = $amount;
            break;
        case 'rsADA':
            $sendersrsADA[$sender] = $amount;
            break;
              case 'rsHOSKY':
            $sendersrsHOSKY[$sender] = $amount;
            break;  case 'HOSKY':
            $sendersHOSKY[$sender] = $amount;
            break;  case 'rsCOMET':
            $sendersrsCOMET[$sender] = $amount;
            break;  case 'COMET':
            $sendersCOMET[$sender] = $amount;
            break;
        case 'rsERG':
            $sendersrsERG[$sender] = $amount;
            break;
        case 'rsRSN':
            $sendersrsRSN[$sender] = $amount;
            break;
        default:
            break;
    }
}

// Sort sender lists by total amount
arsort($sendersERG);
arsort($sendersADA);
arsort($sendersRSN);
arsort($sendersrsADA);
arsort($sendersrsERG);
arsort($sendersrsRSN);
arsort($sendersrsHOSKY);
arsort($sendersHOSKY);
arsort($sendersrsCOMET);
arsort($sendersCOMET);
curl_close($ch);
?>

<?php
// Fetch data from the API
$api_url = 'https://ergcube.com/newprices.php';
$response = file_get_contents($api_url);
$prices = json_decode($response, true);

// Create an associative array of token prices
$tokenPrices = [];
foreach ($prices as $priceData) {
    $tokenPrices[$priceData['ticker']] = $priceData['price'];
}

?><script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


   
 <style>

/* Define the styles for the heading */
h2.gradient-heading {
    position: relative;
    color: white;
}

/* Create the linear gradient line using the ::after pseudo-element */
h2.gradient-heading::after {
    content: '';
    position: absolute;
    bottom: -3px; /* Adjust this value to set the thickness of the line */
    left: 0;
    width: 100%;
    height: 1px; /* Adjust this value to set the height of the line */
    background: linear-gradient(to right, blue, orange); /* Set your gradient colors */
}
    </style>
    <!-- Other columns remain unchanged -->
<br>
<br>
<?php
// Variables to store total amounts for successful and processing events
$successfulTokens = [];
$processingTokens = [];

// Iterate through events to categorize tokens by status and calculate fees
foreach ($data['items'] as $event) {
    $tokenName = $event['lockToken']['name'];
    $tokenAmount = $event['amount'] / (10 ** $event['lockToken']['decimals']);
    $eventStatus = $event['status'];

    // Calculate bridge fee and network fee for each event
    $bridgeFee = $event['bridgeFee'] / (10 ** $event['lockToken']['decimals']);
    $networkFee = $event['networkFee'] / (10 ** $event['lockToken']['decimals']);

    $totalFees = ($bridgeFee + $networkFee);

    if ($eventStatus === 'successful') {
        if (!isset($successfulTokens[$tokenName])) {
            $successfulTokens[$tokenName] = [
                'totalAmount' => $tokenAmount,
                'totalFees' => $totalFees,
                'events' => 1,
            ];
        } else {
            $successfulTokens[$tokenName]['totalAmount'] += $tokenAmount;
            $successfulTokens[$tokenName]['totalFees'] += $totalFees;
            $successfulTokens[$tokenName]['events']++;
        }
    } elseif ($eventStatus === 'processing') {
        if (!isset($processingTokens[$tokenName])) {
            $processingTokens[$tokenName] = [
                'totalAmount' => $tokenAmount,
                'totalFees' => $totalFees,
                'events' => 1,
            ];
        } else {
            $processingTokens[$tokenName]['totalAmount'] += $tokenAmount;
            $processingTokens[$tokenName]['totalFees'] += $totalFees;
            $processingTokens[$tokenName]['events']++;
        }
    }
}
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div class="col-md-12">
    <div class="widget">
        <div class="row">
                 <!-- Total Events Section -->
<div class="col-md-4" style="max-height: 450px; overflow-y: auto;">
    <canvas id="eventsChart" width="400" height="400"></canvas>

    <script>
        // Events Chart
        let eventsCtx = document.getElementById('eventsChart').getContext('2d');
        let eventsChart = new Chart(eventsCtx, {
            type: 'bar',
            data: {
                labels: ['Total Events'],
                datasets: [
                    {
                        label: 'Total Events',
                        data: [<?php echo $totalBridgeEvents; ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.8)',
                    },
                    {
                        label: 'Successful Events',
                        data: [<?php echo $totalSuccessfulEvents; ?>],
                        backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    },
                    {
                        label: 'Processing Events',
                        data: [<?php echo $totalProcessingEvents; ?>],
                        backgroundColor: 'rgba(255, 206, 86, 0.8)',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</div>

            <div class="col-md-4">
                <canvas id="tokenTransfersChart" style="max-height: 300px;"></canvas>
            </div>
            <div class="col-md-4">
                <canvas id="processingTokensChart" style="max-height: 300px;"></canvas>
            </div>
            

        </div>
    </div>
</div>

      
<script>
    // PHP variables to JavaScript
    let tokenData = <?php echo json_encode($successfulTokens); ?>;
    let processingData = <?php echo json_encode($processingTokens); ?>;
    let feesData = <?php echo json_encode($totalFees); ?>;

    // Extracting labels and data for charts
    let tokenNames = Object.keys(tokenData);
    let tokenAmounts = Object.values(tokenData).map(item => item.totalAmount);

    let processingTokenNames = Object.keys(processingData);
    let processingAmounts = Object.values(processingData).map(item => item.totalAmount);

    let feeNames = Object.keys(feesData);
    let feeAmounts = Object.values(feesData);
// Chart for Token Transfers
let tokenTransfersCtx = document.getElementById('tokenTransfersChart').getContext('2d');
let tokenTransfersChart = new Chart(tokenTransfersCtx, {
    type: 'bar',
    data: {
        labels: tokenNames,
        datasets: [{
            label: 'Token Transfers',
            data: tokenAmounts,
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                type: 'logarithmic',
                min: 0, // Set minimum value to avoid negative or zero values
                beginAtZero: true
            }
        }
    }
});


    // Chart for Processing Tokens
    let processingTokensCtx = document.getElementById('processingTokensChart').getContext('2d');
    let processingTokensChart = new Chart(processingTokensCtx, {
        type: 'bar',
        data: {
            labels: processingTokenNames,
            datasets: [{
                label: 'Processing Tokens',
                data: processingAmounts,
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });


</script>






<div class="row">
    <!-- Ergo to Cardano - Processing -->
    <div class="col-md-3" style="max-height: 350px; overflow-y: auto;">
        <h2 class="gradient-heading">Ergo to Cardano - Processing</h2>
        <table class="table">
            <!-- Table headers -->
<thead style="position: sticky; top: 0;">
                <tr>
                    <th>Alert</th>
                    <th>Event ID</th>
                    <th>Asset Name</th>
                    <th>Token Amount</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
    <?php
    $ergoToCardanoProcessingEvents = array_filter($data['items'], function ($event) {
        return ($event['fromChain'] === 'ergo' && $event['toChain'] === 'cardano' && $event['status'] === 'processing');
    });

    if (empty($ergoToCardanoProcessingEvents)) {
        echo '<tr><td colspan="4">Currently no bridge orders from Ergo to Cardano - Processing</td></tr>';
    } else {
        foreach ($ergoToCardanoProcessingEvents as $event) :
    ?>
            <tr>
                   <td>
        <a href="#" class="bell-icon" data-id="<?php echo $event['id']; ?>" data-status="<?php echo $event['status']; ?>">
            <i>ðŸ””</i>
        </a>
    </td>
                <td><a href="<?php echo ($event['fromChain'] === 'ergo') ? 'https://ergexplorer.com/transactions/' . $event['sourceTxId'] : 'https://cardanoscan.io/transaction/' . $event['sourceTxId']; ?>" target="_blank"><?php echo $event['id']; ?></a></td>
                <td><?php echo $event['lockToken']['name']; ?></td>
                <td><?php echo $event['amount'] / (10 ** $event['lockToken']['decimals']); ?></td>
                <td><?php echo date("Y-m-d H:i:s", $event['timestamp']); ?></td>
            </tr>
    <?php
        endforeach;
    }
    ?>
</tbody>

        </table>
    </div>

    <!-- Ergo to Cardano - Successful -->
    <div class="col-md-3" style="max-height: 350px; overflow-y: auto;">
        <h2 class="gradient-heading">Ergo to Cardano - Successful</h2>
        <table class="table">
            <!-- Table headers -->
           <thead style="position: sticky; top: 0;">
    <!-- Your table headers -->


                <tr>
                    <th>Event ID</th>
                    <th>Asset Name</th>
                    <th>Token Amount</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['items'] as $event): ?>
                    <?php if ($event['fromChain'] === 'ergo' && $event['toChain'] === 'cardano' && $event['status'] === 'successful'): ?>
                        <!-- Populate the table rows with Ergo to Cardano - Successful events -->
                        <tr>
                            <td><a href="<?php echo ($event['fromChain'] === 'ergo') ? 'https://ergexplorer.com/transactions/' . $event['sourceTxId'] : 'https://cardanoscan.io/transaction/' . $event['sourceTxId']; ?>" target="_blank"><?php echo $event['id']; ?></a></td>
                            <td><?php echo $event['lockToken']['name']; ?></td>
                            <td><?php echo $event['amount'] / (10 ** $event['lockToken']['decimals']); ?></td>
                            <td><?php echo date("Y-m-d H:i:s", $event['timestamp']); ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Cardano to Ergo - Processing -->
    <div class="col-md-3" style="max-height: 350px; overflow-y: auto;">
         <h2 class="gradient-heading">Cardano to Ergo - Processing</h2>
        <table class="table">
            <!-- Table headers -->
     <thead style="position: sticky; top: 0;">
    <!-- Your table headers -->


                <tr>
                    <th>Event ID</th>
                    <th>Asset Name</th>
                    <th>Token Amount</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
    <?php
    $cardanoToErgoProcessingEvents = array_filter($data['items'], function ($event) {
        return ($event['fromChain'] === 'cardano' && $event['toChain'] === 'ergo' && $event['status'] === 'processing');
    });

    if (empty($cardanoToErgoProcessingEvents)) {
        echo '<tr><td colspan="4">Currently no bridge orders from Cardano to Ergo - Processing</td></tr>';
    } else {
        foreach ($cardanoToErgoProcessingEvents as $event) :
    ?>
            <tr>
                <td><a href="<?php echo ($event['fromChain'] === 'ergo') ? 'https://ergexplorer.com/transactions/' . $event['sourceTxId'] : 'https://cardanoscan.io/transaction/' . $event['sourceTxId']; ?>" target="_blank"><?php echo $event['id']; ?></a></td>
                <td><?php echo $event['lockToken']['name']; ?></td>
                <td><?php echo $event['amount'] / (10 ** $event['lockToken']['decimals']); ?></td>
                <td><?php echo date("Y-m-d H:i:s", $event['timestamp']); ?></td>
            </tr>
    <?php
        endforeach;
    }
    ?>
</tbody>

        </table>
    </div>

    <!-- Cardano to Ergo - Successful -->
    <div class="col-md-3" style="max-height: 350px; overflow-y: auto;">
        <h2 class="gradient-heading">Cardano to Ergo - Successful</h2>
        <table class="table">
            <!-- Table headers -->
<thead style="position: sticky; top: 0;">


                <tr>
                    <th>Event ID</th>
                    <th>Asset Name</th>
                    <th>Token Amount</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['items'] as $event): ?>
                    <?php if ($event['fromChain'] === 'cardano' && $event['toChain'] === 'ergo' && $event['status'] === 'successful'): ?>
                        <!-- Populate the table rows with Cardano to Ergo - Successful events -->
                        <tr>
                            <td><a href="<?php echo ($event['fromChain'] === 'ergo') ? 'https://ergexplorer.com/transactions/' . $event['sourceTxId'] : 'https://cardanoscan.io/transaction/' . $event['sourceTxId']; ?>" target="_blank"><?php echo $event['id']; ?></a></td>
                            <td><?php echo $event['lockToken']['name']; ?></td>
                            <td><?php echo $event['amount'] / (10 ** $event['lockToken']['decimals']); ?></td>
                            <td><?php echo date("Y-m-d H:i:s", $event['timestamp']); ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<br>
<br>
<script>
    $(document).ready(function() {
        // Log user setup alerts in console
        $('.bell-icon').on('click', function(e) {
            e.preventDefault();
            const eventId = $(this).data('id');
            console.log(`User has set an alert for Event ${eventId}`);
        });

        // Function to check for status changes
        function checkStatusChanges() {
            // AJAX request to fetch event status
            $.ajax({
                url: 'https://app.rosen.tech/api/v1/events?offset=0&limit=800', // Your API endpoint
                method: 'GET',
                success: function(response) {
                    // Example: Check the status of the first event
                    const firstEvent = response.data[0]; // Assuming the response is structured as an object with a 'data' property containing an array of events
                    const eventId = firstEvent.id;
                    const eventStatus = firstEvent.status;

                    if (eventStatus === 'successful') {
                        alert(`Event ${eventId} has changed to successful!`);
                    }
                },
                complete: function() {
                    // Call the function again after 30 seconds for continuous checking
                    setTimeout(checkStatusChanges, 30000);
                }
            });
        }

        // Trigger status check on page load
        checkStatusChanges();
    });
</script>
<div class="row">
<?php
// Function to generate a table for each token category with hyperlinked addresses
function generateSenderTable($senders, $tokenName)
{
    ?>
    <div class="col-md-2">
        <h2>Top <?php echo $tokenName; ?> Senders</h2>
        <!-- Display table for top senders of a specific token -->
        <table class="table">
            <!-- Table header -->
            <thead>
                <tr>
                    <th>Sender </th>
                    <th>Amount Sent</th>
                </tr>
            </thead>
            <!-- Table body -->
            <tbody>
                <?php
                $counter = 0;
                foreach ($senders as $sender => $amount) :
                    if ($counter >= 5) break;
                    // Link for the specific address based on the token chain
                    $addressLink = ($tokenName === 'ERG') ? 'https://ergexplorer.com/addresses/' : 'https://cardanoscan.io/address/';
                ?>
                    <tr>
                        <td><a href="<?php echo $addressLink . $sender; ?>" target="_blank"><?php echo substr($sender, 0, 5) . '...' . substr($sender, -5); ?></a></td>
                        <td><?php echo $amount; ?></td>
                    </tr>
                <?php $counter++;
                endforeach; ?>
            </tbody>
        </table>
    </div>
<?php
}

// Generate tables for each token category
generateSenderTable($sendersERG, 'ERG');
generateSenderTable($sendersADA, 'ADA');
generateSenderTable($sendersRSN, 'RSN');
generateSenderTable($sendersrsADA, 'rsADA');
generateSenderTable($sendersrsERG, 'rsERG');
generateSenderTable($sendersrsRSN, 'rsRSN');
generateSenderTable($sendersHOSKY, 'HOSKY');
generateSenderTable($sendersrsHOSKY, 'rsHOSKY');
generateSenderTable($sendersCOMET, 'COMET');
generateSenderTable($sendersrsCOMET, 'rsCOMET');
?>

</div>
    <div class="col-md-2">
    <h2>Total Fees Collected</h2>
    <ul>
        <?php
        $totalFees = [];
        foreach ($successfulTokens as $tokenName => $tokenData) {
            if (!isset($totalFees[$tokenName])) {
                $totalFees[$tokenName] = $tokenData['totalFees'];
            } else {
                $totalFees[$tokenName] += $tokenData['totalFees'];
            }
        }
        foreach ($processingTokens as $tokenName => $tokenData) {
            if (!isset($totalFees[$tokenName])) {
                $totalFees[$tokenName] = $tokenData['totalFees'];
            } else {
                $totalFees[$tokenName] += $tokenData['totalFees'];
            }
        }
        foreach ($totalFees as $tokenName => $fees) : ?>
            <li>
                <h3><?php echo $tokenName; ?></h3>
                <p>Fees: <?php echo number_format($fees, 0, '', ''); ?></p>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
