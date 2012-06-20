<?php
require( 'Pocketstop.php' );

// Pocketstop REST API PHP Example

// Assuming posted form values in $_POST

$email = $_POST['email'];
$firstName = $_POST['firstName'];
$lastName = $_POST['lastName'];
$mobileNumber = $_POST['mobileNumber'];
$address1 = $_POST['address1'];
$address2 = $_POST['address2'];
$city = $_POST['city'];
$state = $_POST['state'];
$zip = $_POST['zip'];
$gender = $_POST['gender'];

// Array of sources you wish to add
$sources = array( 'Source' => 'example.com' );

$args = array(
    'EmailAddress' => $email
    'FirstName' => $firstName,
    'LastName' => $lastName,
    'MobileNumber' => $mobileNumber,
    'Address1' => $address1,
    'Address2' => $address2,
    'City' => $city,
    'State' => $state,
    'Gender' => $gender,
    'ZipCode' => $zip,
    // Optional see $sources defined above
    'CustomerSources' => array( $sources ),
    // Optional meta information about a customer key=>value pairs that extend the base customer record
    'CustomerMeta' => array( 
	    'FavoriteColor' => 'blue', // 1-6 age group identifier (1=18-25, 2=26-35, 3=36-45, 4=46-55, 5=56-65, 6=66+)
	    'BlogUrl' => 'http://blog.example.com'
    )
);

// Optional - use to subscribe user to MailChimp (MailChimp API and List Settings Stored in BECKY)
$args['EmailMarketing'] = array(
	'Action' => 'Subscribe',	// Subscribe/Unsubscribe
	'DoubleOptIn' => 'false', 	// whether or not to send a double-opt in email to the subscriber
	'EmailType' => 'html',		// html/text
    // Optional - Example MailChimp Merge Fields (customize to match MailChimp merge fields)
	'Fields' => array(
		'FNAME' => $firstName,
		'LNAME' => $lastName,
		'MNUMBER' => $lastName,
		'SEX' => $gender,
		'KIDS' => $numberOfKids,
		'ADDRESS' => array(
			'addr1' => $address1,
			'addr2' => $address2,
			'city' => $city,
			'state' => $state,
			'zip' => $zip
		),
        // Optional - MailChimp Interest Groups
        // (name and groups below must match those defined in MailChimp exactly otherwise MailChimp rejects the whole subscriber)
		'GROUPINGS' => array(
			array( 'name' => 'Age Group', 'groups' => '18 to 25',
			array( 'name' => 'Sports', 'groups' => 'Baseball,Basketball,Football' )
		)
	)
);

// Pocketstop-provided API credentials
$accountId = '[pocketstop_provided_account_Id]';
$apiKey = '[pocketstop_provided_api_key]';

// STAGING/TEST API EndPoint
$pocketstop = new PocketstopRestClient( $accountId, $apiKey, 'https://api.pocketstop.net/v1' );

// PRODUCTION API EndPoint ( https://api.pocketstop.com/v1 )
//$pocketstop = new PocketstopRestClient( $accountId, $apiKey );

// POST to Customers resource functions as an UPSERT. 
// Will update if it finds a matching user customer based on MobileNumber or EmailAddress. Insert otherwise.
$response = $pocketstop->request( 'Customers', 'POST', $args );

if ( !$response->IsError ) {
    
    // You can check $response->HttpStatus for further detail 201 = Created, 200 = Accepted (Updated)

    // $response->ResponseText contains raw response

    $customer = $response->ResponseJson;

    print_r($customer);

}

