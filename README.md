#Pocketstop REST API Wrapper for PHP

Pocketstop provides a simple HTTP-based REST API for interacting with the Pocketstop Social CRM Platform. Learn more at [http://www.pocketstop.com][0]

### Installation

Drop the Pocketstop.php file in your directory and...
	
	require( 'Pocketstop.php' );

### Sample Usage

	$accountId = '[your_account_Id]';
	$apiKey = '[your_api_key]';

	$pocketstop = new PocketstopRestClient( $accountId, $apiKey );

#### Insert a Customer

	// EmailAddress or MobileNumber required
	$args = array(
	    'EmailAddress' => 'user@example.com'
	    'FirstName' => 'Joe',
	    'LastName' => 'User'
	);
	
	$response = $pocketstop->request( 'Customers', 'POST', $args );

	if ( !$response->IsError ) {
    
	    // You can check $response->HttpStatus for further detail 201 = Created, 200 = Accepted (Updated)

	    // retrieve customer object from the JSON response
	    $customer = $response->ResponseJson;

	    print_r($customer);

	}

#### Retrieve a Customer by ID

	$customerId = 'xxxxxxxxxxxxxxxxxxxxxxx';
	
	$response = $pocketstop->request( 'Customers/'.$customerID, 'GET' );

	if ( !$response->IsError ) {

	    // retrieve customer object from the JSON response
	    $customer = $response->ResponseJson;

	    print_r($customer);

	}

#### Search for a Customer by EmailAddress

	$customer_email = 'user@example.com';
	
	$response = $pocketstop->request( "Customers?filter=EmailAddress eq '$customer_email'", 'GET' );

	if ( !$response->IsError ) {

		$list = $response->ResponseJson;

		if ( $list->RecordCount > 0 ) {

		    // retrieve first customer from the list
		    $customer = $list->Customers[0];

		    print_r($customer);

	   	}

	}

See included [example.php][1] for a more detailed example.

[0]: http://www.pocketstop.com
[1]: https://github.com/pocketstop/sdk-php/blob/master/src/example.php