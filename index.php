<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	<title>Insert title here</title>
	<script type="text/javascript" src="static/javascript/jquery-1.4.3.min.js"></script>
	<script type="text/javascript" src="static/javascript/jquery.mousewheel.min.js"></script>
	<script type="text/javascript" src="static/javascript/rtree.js"></script>
	<script type="text/javascript" src="static/javascript/a3o_oo.js"></script>
	<script type="text/javascript">
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
			mouswheel: zoom map<br />
			right mousbutton: pan map<br />
			left mousebutton: find path<br /> 
			All assets and static ressources found on this webpage are not part of this project but originate from the TripleA project and are licenced under GNU GPL.<br /> 
			Please refer to <a href="http://triplea.sourceforge.net">http://triplea.sourceforge.net</a> for more information on the TripleA project.</p>
</body>
</html>