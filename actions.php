<?php

include 'common.php';
initialize_roots();

// print_r($root_ids);
//this is the data packet that will be sent to the client
function newDataPacket(){
    $data_packet = array("sketch"=>"",
                        "sketch_name"=>"",
                        "debug"=>"",
                        "message"=>"",
                        "data"=>"",
                        );
    return $data_packet;
}

function addToRoots($skObj){
    if(!in_array($skObj['uid'], $GLOBALS['root_ids'])){
        array_push($GLOBALS['root_ids'], $skObj['uid']);
        update_roots($GLOBALS['root_ids']);
    }
}

function saveToFile($sketch, $directory){
    $path = $directory.$sketch['uid'].'.json';
    $fp = fopen($path, 'w');
    fwrite($fp, json_encode($sketch));
    fclose($fp);

    if($sketch['parent'] == 'None'){
        addToRoots($sketch);
    }else{
        //add this to the child list of the parent sketch - pending
    }

    return $path;
}

function getJsonStr($filePath){
    $json_str = file_get_contents($filePath);
    return $json_str;
}

$self_path = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
$path  = pathinfo($self_path);
$app_file = 'scribble_old.html';
$app_path = 'http://'.$path['dirname'].'/'.$app_file;

if(isset($_POST['action']) && $_SERVER['HTTP_REFERER'] == $app_path){
    if($_POST['action'] == 'save'){
        //second param true does any needed conversions on input objects
        $sketch = json_decode($_POST['sketch'], true);
        $save_path = saveToFile($sketch, $sketch_dir);
        //building the data_packet to send
        $data_packet = newDataPacket();
        $data_packet['message'] = "succesfully saved ";
        $data_packet['sketch_name'] = $sketch['name'];
        echo json_encode($data_packet);
    }elseif($_POST['action'] == 'load'){
        $filePath = $sketch_dir.$_POST['sketch_id'].'.json';
        $sketch_obj = getSketchObject($_POST['sketch_id']);

        //building the data packet to send
        $data_packet = newDataPacket();
        $data_packet['sketch_name'] = $sketch_obj['name'];
        $data_packet['message'] = 'Succesfully loaded ';
        $data_packet['sketch'] = getJsonStr($filePath);
        //adding debug message if needed
        $data_packet['debug'] = json_encode($GLOBALS['root_ids']);
        echo json_encode($data_packet);
    }
}
?>