<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>A3O - a browser-based TripleA clone using modern web-technlogies.</title>
	<script type="text/javascript" src="static/javascript/jquery-1.4.3.min.js"></script>
	<script type="text/javascript" src="static/javascript/jquery.mousewheel.min.js"></script>
	<script type="text/javascript" src="static/javascript/rtree.js"></script>
	<script type="text/javascript" src="static/javascript/a3o_oo.js"></script>
	<script>
		jQuery(document).ready(function(){
			A3O.loadRessources( <?php echo (int) $_REQUEST['g']; ?>, function(){ $('#loading').hide();  A3O.setup( document.getElementById('map').getContext('2d') ); });
			jQuery('.button').mousedown( function() {
				jQuery(this).addClass('pressed');
			}).mouseup( function() {
				jQuery(this).removeClass('pressed');
			});
		});
	</script>
	<style type="text/css">
		.button 
		{
			border: 1px solid black;
			background-color: pink;
			cursor:pointer;
		}
		.button.pressed
		{
			border: 2px solid black;
			background-color: light-pink;
		}
		#map 
		{
			/* prevent selection of canvas.
			 * most browser allow selection of elements by double clicking. 
			 * because double clicks are a regular A3O user interaction we
			 * need to prevent the browser from selecting the canvas upon
			 * double click or else the user will constantly select the 
			 * canvas and other elements.
			 */
			-moz-user-select: none; 
    		-khtml-user-select: none;
		
		}
	</style>
</head>
<body>
			This is a development version of 'A3O', a browser based Tripple A / Axis & Allies clone. It is for testing only.<br/>
			<b id="loading">Loading ressources... please be patient.</b><br/>
		
			<canvas height="600" width="1000" id="map" style="border:solid black;">
			You need HTML5 Canvas to view this webpage. You can get a HTML5 capable browser <a href="http://www.google.com/chrome/">here</a>.
			</canvas>
			<div style="float:right">
				<div class="button" onclick="A3O.drawBoard(false);A3O.swapBuffers();">Redraw Board</div>
				<div class="button" onclick="A3O.drawUnits();A3O.swapBuffers();">Redraw Units</div>
			</div>
			<br/>
			<p>
			right mousbutton: pan map<br />
			left mousebutton: pick up (german) units<br />
			right mousbutton (while unit is grabbed): return unit<br />
			left mousebutton (while unit is grabbed): drop unit here<br /></p> 
			<p>
			Please visit the projects <a href="https://github.com/DanielBaulig/A3O/">github page</a>.
			This software is (c) 2010 by Daniel Baulig and licensed under a modified MIT license.<br/>
			This software uses parts of the <a href="http://triplea.sourceforge.net/mywiki">TripleA</a> and other free software projects. Please
			refer to the <a href="README">README</a> and <a href="LICENSE">LICENSE</a> files for more information on license terms and used third party software.
			</p>
</body>
</html>