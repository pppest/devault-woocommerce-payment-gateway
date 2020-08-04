<?php
/* this section adds the DeVault payment info to the checkout page
* and sets hooks to pass the tx info to the order processing
*/

add_filter( 'woocommerce_gateway_description', 'techiepress_devault_description_fields', 20, 2 );
add_action( 'woocommerce_checkout_process', 'techiepress_devault_description_fields_validation' );
add_action( 'woocommerce_checkout_update_order_meta', 'techiepress_checkout_update_order_meta', 10, 1 );
add_action( 'woocommerce_admin_order_data_after_billing_address', 'techiepress_order_data_after_billing_address', 10, 1 );
add_action( 'woocommerce_order_item_meta_end', 'techiepress_order_item_meta_end', 10, 3 );

function techiepress_devault_description_fields( $description, $payment_id ) {
    if ( 'devault' !== $payment_id ) {
        return $description;
    }
    $description = explode( '/', $description );
    $dvt_gateway = WC()->payment_gateways->payment_gateways()['devault'];
    $devault_total_val = WC_Gateway_devault::calc_dvt_total( WC()->cart->total ) ;
    $store_address = $dvt_gateway->store_devault_address;
    $timeout = $dvt_gateway->devault_timeout;
    $msg = get_permalink( wc_get_page_id( 'shop' ) );
    $req_uri = $store_address . '?amount=' . $devault_total_val . '&msg=' . $msg;

    ob_start();
    echo '
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css">
					<script type="text/javascript" charset="utf-8" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
					<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery-migrate/3.3.1/jquery-migrate.js">	</script>
					<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
                    <script type="text/javascript">
					console.log("Starting DeVault payment process.");
					 $(function () {
						var current;
						var max = 100;
					  var initial = Math.floor(Date.now()/1000);
					  var duration = '.$timeout.';
						var payment_verified = false;
					  if(duration){
					     	var current = 100 * (Math.floor(Date.now()/1000) - initial)/duration;
							$("#progressbar").progressbar({ value: current, max: max, });
							function update() {
					            current = 100 * (Math.floor(Date.now()/1000) - initial)/duration;
							    $("#progressbar").progressbar({ value: current });
							    if ( (current >= max) != payment_verified ) {
                      //window.location.href = window.location.href;
							        $("#container").html("'.$description[8].'");
							        }
							    }
						    };
					    var interval = setInterval(update, 10);
                    });
					      // The query we constructed from step 2.
					      var store_dvt_address = "'.$store_address.'".substring(8);
						    console.log("store address: " + store_dvt_address)
							var query1 = {"v": 3,"q": {"find": {},"limit": 10}}; // for testing. shows all txs
							var query2 = { "v": 3, "q": { "find": { "out.e.v": "'.($devault_total_val*100000000).'", "out.e.a": store_dvt_address }, "limit": 1, } };
                            console.log( query2);
                            // Turn the query into base64 encoded string.
					      // This is required for accessing a public bitdb node
					      var b64 = btoa(JSON.stringify(query1));
					      var wss_address = "https://bitdb.exploredvt.com/s/";
							console.log("uri: " + wss_address);
							let bitsocket = new EventSource( wss_address + b64 );
					        console.log("starting bitsocket");
							bitsocket.onmessage = function(message) {
					            console.log(message.data);
                                let eventMessage = JSON.parse(message.data);
                                if(eventMessage.type != "open" && eventMessage.data.length >= 1){
                                    var outs = eventMessage.data[0].out;
                                    console.log(outs);
                                    var i = 0;
                                    var len = outs.length;
                                    for (; i < len; ) {
                                        console.log("out.e.v:" + outs[i].e.v);
                                        if (  outs[i].e.a  == store_dvt_address ) {
                                            if (outs[i].e.v  ==  '. ($devault_total_val * 100000000) .'){
                                            document.getElementById("confirmed").innerHTML = "'.$description[7].'<br> ";
                                            document.getElementById("txid_show").innerHTML = eventMessage.data[0].tx.h ;
                                            $( "#txid" ).val( String( eventMessage.data[0].tx.h ) );
                                            $( "#dvttotal" ).val( ( outs[i].e.v / 100000000 ) );
                                            payment_verified = true;
                                            bitsocket.close();
                                            document.getElementById("progressbar").remove();
                                            document.getElementById("place_order").click();
                                            }
                                        }
                                            i++;
                                                }}
                                            };
								</script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@100;400&display=swap" rel="stylesheet">
    <style>
    #txid {display: none;}
    #dvttotal {display: none;}
    .box{ border: 1px solid; border-radius: 16px; width: 19em; text-align: center; }
    .devault{ font-family: "Montserrat", sans-serif; font-weight: bold; }
        .devault-btn { background-color: #3d35c6; border-radius: 5px; padding:10px; color: white; }
        .dvtbar > .ui-progressbar-value { background: #1c71d9; }
    </style>
    <div class="box devault">
        <div id="container" style="width:16em; margin:auto; class="devault" >
            <div id="invoice" class="devault" ><h3><b>'.$description[1].'</h3</b></div>
                <p >'.$description[2].'</b></p><br style="clear: both;" />
                <p id="amount" style="font-size:120%;" >'.$description[3].': <b>'.$devault_total_val.' DVT</b></p><br style="clear: both;" />
                <div style="devault">'.$description[4].': '.$store_address.'</div><br style="clear: both;" />
                    <div class="devault-btn" style=" border-radius: 5px; padding:1px;">
                        <a id="link" style="color:#ffffff; text-decoration:none;" href="'.$req_uri.'" target="_blank" >'.$description[5].'</a>
                    </div><br style="clear: both;" />
                    <div><img style="all: unset; text-align: center; " src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=Example" alt="'.$req_uri.'" ></div><br style="clear: both;" />
                    <div id="confirmed" >'.$description[6].'</div>
                    <div id="txid_show" ></div>

            </div>';

    woocommerce_form_field(
        'txid',
        array(
            'type' => 'text',
            'label' =>__( '', 'devault-payments-woo' ),
            'class' => array( 'form-row', 'form-row-wide' ),
            'required' => true,
        ),
    );
    woocommerce_form_field(
        'dvttotal',
        array(
            'type' => 'text',
            'label' =>__( '', 'devault-payments-woo' ),
            'class' => array( 'form-row', 'form-row-wide' ),
            'required' => true,
        ),
    );

    echo '
        <div id="progressbar" class="dvtbar"></div>
        <p style="font-size:80%;">Powered by DeVault</p><br style="clear: both;" />
        </div>
        ';

    $description = ob_get_clean();

    return $description;
}


function techiepress_devault_description_fields_validation() {
    if( 'devault' === $_POST['txid'] && ! isset( $_POST['txid'] ) || empty( $_POST['txid'] ) ) {
        wc_add_notice( 'Transaction not confirmed!.', 'error' );
    }
}

function techiepress_checkout_update_order_meta( $order_id ) {
    if( isset( $_POST['txid'] ) || ! empty( $_POST['txid'] ) ) {
       update_post_meta( $order_id, 'txid', $_POST['txid'] );
       update_post_meta( $order_id, 'dvttotal', $_POST['dvttotal'] );
    }
}


//adds devault payment info to admin order page
function techiepress_order_data_after_billing_address( $order ) {
    echo '<p><strong>' . __( 'DeVault payment tx id:', 'devault-payments-woo' ) . '</strong><br><a href="https://exploredvt.com/tx/' . get_post_meta( $order->get_id(), 'txid', true ) . '" target="_blank" >' . get_post_meta( $order->get_id(), 'txid', true ) . '</a></p>';
    echo '<p><strong>' . __( 'DeVault payment total:', 'devault-payments-woo' ) . '</strong><br>' . get_post_meta( $order->get_id(), 'dvttotal', true ) . ' DVT</p>';

}

//adds devault payment info to user thankyou page
function techiepress_order_item_meta_end( $item_id, $item, $order ) {
    echo '<p><strong>' . __( 'DeVault payment tx id:', 'devault-payments-woo' ) . '</strong><br><a href="https://exploredvt.com/tx/' . get_post_meta( $order->get_id(), 'txid', true ) . '" target="_blank" >' . get_post_meta( $order->get_id(), 'txid', true ) . '</a></p>';
    echo '<p><strong>' . __( 'DeVault payment total:', 'devault-payments-woo' ) . '</strong><br>' . get_post_meta( $order->get_id(), 'dvttotal', true ) . ' DVT</p>';
}
