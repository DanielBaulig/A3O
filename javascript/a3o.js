function error(message)
{
	alert(message);
}

function drawPolygon(canvasContext, vertices, fillColor, strokeColor)
{
	if (vertices.length > 0)
	{
		if(strokeColor != null)
		{
			canvasContext.strokeStyle = strokeColor;
		}
		else
		{
			canvasContext.strokeStyle = 'black';
		}
		if (fillColor != null)
		{
			canvasContext.fillStyle = fillColor;
		}
		else
		{
			canvasContext.fillStyle = 'white';
		}
		
		canvasContext.beginPath();
		if (vertices[0].length == 2)
		{
			canvasContext.moveTo(vertices[0][0], vertices[0][1]);
		}
		else
		{
			error('Cannot draw polygon: vertex does not have exactly 2 components!');
		}
		for(var i = 1; i < vertices.length; i++)
		{
			if (vertices[i].length == 2)
			{
				canvasContext.lineTo(vertices[i][0], vertices[i][1]);
			}
			else
			{
				error('Cannot draw polygon: vertex does not have exactly 2 components!');
			}
		}
		canvasContext.closePath();
		if (fillColor != null)
		{
			canvasContext.fill();
		}
		canvasContext.stroke();
	}
}

function drawTile(canvasContext, tile)
{
	var fill = null;
   	if (tile.type == 1)
   	{
   		fill = 'blue';
   	}
   	else
   	{
   		fill = 'white';
   	}
   	drawPolygon(canvasContext, tile.vertices, fill);
   	canvasContext.fillStyle = 'black';
   	canvasContext.moveTo(tile.center[0], tile.center[1]);
   	canvasContext.beginPath();
   	canvasContext.arc(tile.center[0], tile.center[1], 5, 0, 6.283185307179586, true);
   	canvasContext.fill();
}

// based on the C-code from http://www.ecse.rpi.edu/Homepages/wrf/Research/Short_Notes/pnpoly.html
function isPointInPolygon(vertices, x, y)
{
  var i, j, c = false;
  for (i = 0, j = vertices.length-1; i < vertices.length; j = i++) {
    if ( ((vertices[i][1]>y) != (vertices[j][1]>y)) &&
	 (x < (vertices[j][0]-vertices[i][0]) * (y-vertices[i][1]) / (vertices[j][1]-vertices[i][1]) + vertices[i][0]) )
       c = !c;
  }
  return c;
}

