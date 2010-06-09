function error(message)
{
	alert(message);
}

function drawPolygon(canvasContext, vertices, fillColor)
{
	if (vertices.length > 0)
	{
		canvasContext.strokeStyle = 'black';
		if (fillColor != null)
		{
			canvasContext.fillStyle = fillColor;
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