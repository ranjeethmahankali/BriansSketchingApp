<?php
require_once __DIR__ . '/src/Facebook/autoload.php';
include 'common.php';
include 'login.php';


$self_path = $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
$path  = pathinfo($self_path);
$homePageURL = array(
    'http://'.$path['dirname'].'/index.php',
    'http://'.$path['dirname'].'/',
    'http://'.$path['dirname']
    );
//if referer url is not right, redirect to home page
$refURL = $url=strtok($_SERVER["HTTP_REFERER"],'?');
if(!in_array($refURL, $homePageURL)){
    $redirect = $homePageURL[0];
    header("Location: $redirect");
    die();
}
//force user to login if not already logged in
if (!isset($_SESSION['facebook_access_token'])){
    print<<<END
    <script type="text/javascript">
        function loginToFb(){
            window.close();
            window.opener.location = '$loginUrl';
        }
    </script>
END;
    print"<p>Log in with facebook to use the app: ";
    print"<button onclick=\"loginToFb()\">Login with Facebook</button></p>";
    //$_SESSION['currentURL'] = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    //header("Location: $loginUrl");
    //$_SESSION['fromLogin'] = true;
    die();
}

$script = "";

if(isset($_GET['uid'])){
    if(file_exists($GLOBALS['sketch_dir'].$_GET['uid'].'.json')){
        $uid = $_GET['uid'];
        $script = <<<END
            <script type="text/javascript">
                loadSketch('$uid');
            </script>
END;
    }else{
        $script = <<<END
        <script type="text/javascript">
            alert('Could not find the sketch file');
        </script>
END;
    }
}else{
    $script = <<<END
        <script type="text/javascript">
            console.log('Nothing to Load');
        </script>
END;
}

print<<<END
<!DOCTYPE HTML>
<html>
    <head>
        <title>Scribble Pad</title>

        <link type="text/css" href = "scribbleCss.css" rel = "stylesheet"></link>
        <link href="https://fonts.googleapis.com/css?family=VT323" rel="stylesheet">

        <script src = "http://wzrd.in/standalone/uuid@latest"></script>
    </head>
    <body>
        <div id = "wrapper">
            <div id = "topBar">
                <div id = "topBar1" class="topBar_">
                    <button id = "save_btn" class="control">Save</button>

                    <div id = "fillColorTool" class="control">Fill
                        <input type = "color" name="fillColor" value="#000000" id="fillColor" onchange="updatePen()"/>
                    </div>
                    <div id = "strokeColorTool" class="control">Stroke
                        <input type = "color" name="strokeColor" value="#000000" id="strokeColor" onchange="updatePen()"/>
                    </div>
                    <div id = "lineWidthTool" class="control">Line Width:
                        <input type = "number" name="lineWidth" value="5" id="lineWidth" onchange="updatePen()" min="1" max="20"/>
                    </div>

                    <button id = "clear_btn" class="control">Clear</button>
                </div>
                <div id="topBar2" class="topBar_">
                    <div id = "chain_box" style="display:none" class="control">
                        <input type = "checkbox" name = "chain" value = "chain" id = "chainCheckbox"/>Chain
                    </div>
                    <div id = "fill_box" style="display:none" class="control">
                        <input type = "checkbox" name = "chain" value = "chain" id = "fillCheckbox"/>Fill
                    </div>
                    <div id = "reverse_box" style="display:none" class="control">
                        <input type = "checkbox" name = "chain" value = "chain" id = "reverseCheckbox"/>clockwise
                    </div>
                    <div id = "imgUpload_box" style="display:none" class="control">
                        <input type = "file" name = "imgUpload" id = "imgUpload" accept = "image/*" onchange="loadImage()"></input>
                    </div>
                    <div id = "polygonToolBox" style="display:none" class="control">
                        No. of Sides: <input id="numOfSides" type="number" name="numOfSides" min="3" max="30" value="5" onchange="updateSides()"/>
                    </div>
                    <div id = "text_inputBox" style="display:none">
                        <div class="control">
                            <input typ="text" id="text_input" width="100" placeholder="type your text here ..." maxlength="50" name="textTool"/>
                        </div>
                        <div class="control">
                            Size (10 to 50): <input id="textSize" class="control" type="number" name="textSize" min="10" max="50" value="20"/>
                        </div>
                    </div><br />
                </div>
            </div>
            <div id = "midBar">
                <div id = "leftToolBar">
                    <ul>
                        <li class="tool" onclick="loadTool(this)"><img src="icons/line.png" alt="line"></li>
                        <li class="tool" onclick="loadTool(this)"><img src="icons/rectangle.png" alt="rectangle"></li>
                        <li class="tool" onclick="loadTool(this)"><img src="icons/polygon.png" alt="polygon"></li>
                        <li class="tool" onclick="loadTool(this)"><img src="icons/arc.png" alt="circle"></li>
                        <li class="tool" onclick="loadTool(this)"><img src="icons/elipse.png" alt="ellipse"></li>
                        <li class="tool" onclick="loadTool(this)"><img src="icons/bezier.png" alt="curve"></li>
                        <li class="tool" onclick="loadTool(this)"><img src="icons/freeHand.png" alt="freehand"></li>
                        <li class="tool" onclick="loadTool(this)"><img src="icons/fill.png" alt="floodFill"></li>
                        <li class="tool" onclick="loadTool(this)"><img src="icons/eraser.png" alt="eraser"></li>
                        <li class="tool" onclick="loadTool(this)"><img src="icons/text2.png" alt="text"></li>
                        <li class="tool" onclick="loadTool(this)"><img src="icons/image1.png" alt="image"></li>
                    </ul>
                </div>
                <div id = "canvas_box">
                    <canvas id = "canvas_1" height = "500px" width = "500px" class = "scribblePad"></canvas>
                    <canvas id = "canvas_2" height = "500px" width = "500px" class = "scribblePad"></canvas>
                    <canvas id = "canvas_3" height = "500px" width = "500px" class = "scribblePad"></canvas>
                    <div id = "canvas_screen" class = "scribble"></div>
                </div>
            </div>
            
            <p id = "help_text">Select a tool to start drawing</p>
            
        </div>

        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
        <!--script src="jquery-1.9.1.min.js"></script-->
        <script type = "text/JavaScript" src = "funcLibrary.js"></script>
        <script type = "text/JavaScript" src = "scribbleScript.js"></script>
        $script

        <script type="text/javascript">
            //when scribble app is closed, this refreshes the main sketch tree page
            window.onunload = refreshParent;
            function refreshParent() {
                window.opener.location.reload();
            }
        </script>
    </body>
</html>
END;
?>