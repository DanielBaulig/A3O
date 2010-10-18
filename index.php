<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<title>Insert title here</title>
 <script src="http://jquery.com/src/jquery-latest.js"></script>
 <script src="javascript/jQuery_mousewheel_plugin.js"></script>
 <script src="static/javascript/a3o_oo.js"></script>
 <script src="static/json/games/big_world/polygons.json"></script>
 <script type="text/javascript">

	$(document).ready(function(){
		A3O.loadRessources( function(){ $('#loading').hide();  A3O.setup( document.getElementById('map').getContext('2d') ); });		
	});
 </script>

</head>
<body>
			This is a development version of 'A3O', a browser based Tripple A / Axis & Allies clone. It is for testing only.<br/>
			<b id="loading">Loading ressources... please be patient.</b><br/>
		
			<canvas height="600" width="1000" id="map" style="border:solid black;">
			You need HTML5 Canvas to view this webpage. You can get a HTML5 capable browser <a href="http://www.google.com/chrome/">here</a>.
			</canvas><br/>
			mouswheel: zoom map<br />
			right mousbutton: pan map<br />
			left mousebutton: find path<br /> 
			All assets and static ressources found on this webpage are not part of this project but originate from the TripleA project and are licenced under GNU GPL.<br /> 
			Please refer to <a href="http://triplea.sourceforge.net">http://triplea.sourceforge.net<a/> for more information on the TripleA project.
</body>
</html>