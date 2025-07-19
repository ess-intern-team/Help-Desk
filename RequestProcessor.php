<?php

// C:\xampp\htdocs\helpdesk\RequestProcessor.php

class RequestProcessor
{

    private $data;

    public function __construct()
    {
        $this->data = [];
    }

    /**
     * Processes incoming request data (e.g., GET, POST, or custom data).
     * @param array $requestData Optional: Data to process. If not provided, it might use $_GET or $_POST.
     * @return bool True if processing was successful, false otherwise.
     */
    public function process(array $requestData = [])
    {
        if (empty($requestData)) {
            if (!empty($_GET)) {
                $requestData = $_GET;
            } elseif (!empty($_POST)) {
                $requestData = $_POST;
            }
        }

        if (empty($requestData)) {
            $this->logMessage("No request data to process.", "warning");
            return false;
        }

        $this->logMessage("Processing request data: " . json_encode($requestData));

        if (isset($requestData['action'])) {
            switch ($requestData['action']) {
                case 'get_tickets':
                    // This case is fine because fetchTicketsFromDatabase is called internally
                    $this->data['tickets'] = $this->fetchTicketsFromDatabase();
                    $this->logMessage("Fetched tickets.");
                    break;
                case 'submit_ticket':
                    if (isset($requestData['subject']) && isset($requestData['description'])) {
                        $ticketId = $this->saveTicketToDatabase($requestData['subject'], $requestData['description']);
                        if ($ticketId) {
                            $this->data['status'] = 'success';
                            $this->data['message'] = 'Ticket submitted successfully with ID: ' . $ticketId;
                            $this->data['ticket_id'] = $ticketId;
                            $this->logMessage("New ticket submitted: " . $ticketId);
                        } else {
                            $this->data['status'] = 'error';
                            $this->data['message'] = 'Failed to submit ticket.';
                            $this->logMessage("Failed to submit ticket.", "error");
                            return false;
                        }
                    } else {
                        $this->data['status'] = 'error';
                        $this->data['message'] = 'Missing subject or description for ticket submission.';
                        $this->logMessage("Missing data for ticket submission.", "error");
                        return false;
                    }
                    break;
                case 'get_pending_requests':
                    $this->data['pending_requests'] = $this->getPendingRequests();
                    $this->logMessage("Fetched pending requests.");
                    break;
                case 'get_it_specialists':
                    $this->data['it_specialists'] = $this->getITSpecialists();
                    $this->logMessage("Fetched IT specialists.");
                    break;
                case 'get_forwarded_requests':
                    $this->data['forwarded_requests'] = $this->getForwardedRequests();
                    $this->logMessage("Fetched forwarded requests.");
                    break;
                case 'count_resolved_last_week':
                    $this->data['resolved_count_last_week'] = $this->countResolvedRequestsLastWeek();
                    $this->logMessage("Counted resolved requests for last week.");
                    break;
                default:
                    $this->data['status'] = 'info';
                    $this->data['message'] = 'No specific action requested.';
                    $this->logMessage("No specific action requested.");
            }
        }

        return true;
    }

    /**
     * Returns the processed data.
     * @return array
     */
    public function getProcessedData()
    {
        return $this->data;
    }

    /**
     * Public method to get all tickets.
     * Calls the private fetchTicketsFromDatabase method.
     * @return array
     */
    public function getAllTickets()
    { // NEW PUBLIC METHOD
        return $this->fetchTicketsFromDatabase();
    }

    /**
     * Fetches pending requests.
     * @return array
     */
    public function getPendingRequests()
    {
        $this->logMessage("Fetching pending requests.");
        return [
            [
                'id' => 3,
                'user_name' => 'Alice Johnson',
                'title' => 'Network connectivity issue',
                'priority' => 'High',
                'status' => 'Pending',
                'assigned_to' => 'John Doe',
                'created_at' => '2025-07-13 10:00:00'
            ],
            [
                'id' => 5,
                'user_name' => 'Bob Williams',
                'title' => 'Software update failed',
                'priority' => 'Medium',
                'status' => 'Pending',
                'assigned_to' => 'Jane Smith',
                'created_at' => '2025-07-12 14:30:00'
            ],
        ];
    }

    /**
     * Fetches a list of IT specialists.
     * @return array
     */
    public function getITSpecialists()
    {
        $this->logMessage("Fetching IT specialists.");
        return [
            ['id' => 101, 'name' => 'John Doe', 'email' => 'john.doe@example.com', 'department' => 'IT Support'],
            ['id' => 102, 'name' => 'Jane Smith', 'email' => 'jane.smith@example.com', 'department' => 'Network Operations'],
            ['id' => 103, 'name' => 'Peter Jones', 'email' => 'peter.jones@example.com', 'department' => 'Software Development'],
        ];
    }

    /**
     * Fetches forwarded requests.
     * @return array
     */
    public function getForwardedRequests()
    {
        $this->logMessage("Fetching forwarded requests.");
        return [
            [
                'id' => 7,
                'user_name' => 'Charlie Brown',
                'title' => 'Server access request',
                'status' => 'Forwarded',
                'forwarded_to' => 'Server Admin Team',
                'forwarded_at' => '2025-07-11 09:15:00'
            ],
            [
                'id' => 9,
                'user_name' => 'Diana Prince',
                'title' => 'New user account creation',
                'status' => 'Forwarded',
                'forwarded_to' => 'HR Department',
                'forwarded_at' => '2025-07-10 11:45:00'
            ],
        ];
    }

    /**
     * Counts resolved requests in the last week.
     * @return int
     */
    public function countResolvedRequestsLastWeek()
    {
        $this->logMessage("Counting resolved requests for last week.");

        $resolvedTickets = [
            ['id' => 4, 'subject' => 'Software bug fix', 'status' => 'Resolved', 'resolved_date' => '2025-07-09 15:00:00'],
            ['id' => 6, 'subject' => 'Email client configuration', 'status' => 'Closed', 'resolved_date' => '2025-07-10 10:00:00'],
            ['id' => 8, 'subject' => 'Hardware replacement', 'status' => 'Resolved', 'resolved_date' => '2025-07-01 08:00:00'],
            ['id' => 10, 'subject' => 'Account lockout', 'status' => 'Resolved', 'resolved_date' => '2025-07-13 16:00:00'],
        ];

        $count = 0;
        $oneWeekAgo = new DateTime('now', new DateTimeZone('EAT'));
        $oneWeekAgo->modify('-7 days');
        $oneWeekAgo->setTime(0, 0, 0);

        $this->logMessage("Counting resolved requests from: " . $oneWeekAgo->format('Y-m-d H:i:s'));

        foreach ($resolvedTickets as $ticket) {
            if (isset($ticket['resolved_date']) && ($ticket['status'] === 'Resolved' || $ticket['status'] === 'Closed')) {
                try {
                    $resolvedDate = new DateTime($ticket['resolved_date'], new DateTimeZone('EAT'));
                    if ($resolvedDate >= $oneWeekAgo) {
                        $count++;
                    }
                } catch (Exception $e) {
                    $this->logMessage("Invalid resolved_date format for ticket ID " . ($ticket['id'] ?? 'unknown') . ": " . $ticket['resolved_date'], "error");
                }
            }
        }
        return $count;
    }

    // --- Existing Helper/Private Methods ---

    private function fetchTicketsFromDatabase()
    {
        return [
            [
                'id' => 1,
                'user_name' => 'Alice Johnson',
                'subject' => 'Printer not working',
                'status' => 'Open',
                'assigned_to' => 'John Doe',
                'created_at' => '2025-07-14 09:00:00'
            ],
            [
                'id' => 2,
                'user_name' => 'Bob Williams',
                'subject' => 'Software installation',
                'status' => 'Closed',
                'assigned_to' => 'Jane Smith',
                'created_at' => '2025-07-10 11:00:00'
            ],
            [
                'id' => 3,
                'user_name' => 'Charlie Brown',
                'subject' => 'Network connectivity issue',
                'status' => 'Pending',
                'assigned_to' => 'John Doe',
                'created_at' => '2025-07-13 10:00:00'
            ],
        ];
    }

    private function saveTicketToDatabase($subject, $description)
    {
        $newTicketId = rand(100, 999);
        $this->logMessage("Simulating saving ticket: Subject='" . $subject . "', Description='" . $description . "'");
        return $newTicketId;
    }

    private function logMessage($message, $level = "info")
    {
        $logFile = __DIR__ . '/request_processor.log';
        file_put_contents($logFile, date('[Y-m-d H:i:s]') . " [$level] " . $message . PHP_EOL, FILE_APPEND);
    }
}
