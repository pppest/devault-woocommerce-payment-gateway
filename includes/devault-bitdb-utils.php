<?php
function verify_tx( $txid, $amount, $unconfirmed, $store_address){
   
    $json_url			= 'curl https://bitdb.exploredvt.com/q/';
    
    $query 				= array(
                            "v" => 3,
                            "q" => array(
                            "find" => array(
                                "tx.h" => $txid),
                            "limit" => 1
                                )
                            );

    $b64 				= base64_encode( json_encode( $query, JSON_UNESCAPED_SLASHES ));
    $json_url 			.= $b64;
    $json 				= shell_exec( $json_url );
    $decode 			= json_decode( $json, true );
    $verified			= 0;

    // loop thru unconfirmed if on
    $out 				= $decode['u'][0]['out'];

    foreach( $out as $index => $item ){
        if( $item['e']['a'] == $this->store_dvt_address && ( ($item['e']['v']) == $total ) ) {  
            $verified = 1;
            break;
            }
        }

    //loop thru confirmed
    $out 				= $decode['c'][0]['out'];

	//check and get bitdb amount
    foreach( $out as $index => $item ){
        if ( $unconfirmed && $verified ){ return $verified; }
        if( $item['e']['a'] == $this->store_dvt_address && ( ($item['e']['v']) == $total ) ) {  
            $verified = 1;
            break;
            }
        }
        
    return verified;
}