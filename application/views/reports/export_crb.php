public function export_crb($format = 'xlsx') {
    // Initialize cURL session
    $ch = curl_init();

    // Set the URL of the endpoint through the shared helper
    $url = report_service_url('generate-report-crb');

    // Prepare the data to be sent
    $data = [
        "report_type" => "CRB reports",
        "user" => $this->session->userdata('Firstname')." ".$this->session->userdata('Lastname'),
        "user_id" => $this->session->userdata('user_id'),
        "format" => $format // Add format parameter
    ];

    // Convert the data array to JSON
    $jsonData = json_encode($data);

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

    // Execute the cURL request
    $response = curl_exec($ch);

    // Check for errors
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    } else {
        // Print the response
        $this->toaster->success('Success, Report is being processed you may do other things and come back for progress');
        redirect(site_url('report'));
    }

    // Close the cURL session
    curl_close($ch);
}