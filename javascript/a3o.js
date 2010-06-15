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

function getAssocArrayLength(tempArray) 
{
   var result = 0;
   for ( tempValue in tempArray ) {
      result++;
   }
	
   return result;
}

// sadly not suitable for our problem, because there is no relyable heuristic
// for our unregular formed and sized nodes
function AStar(tiles, start, end, legal)
{
	var open_list = {}
	open_list[start] =
	{ 
		"Cost" 		 : 0,
		"AssumedRest": 0,
		"Sum"  		 : 0,
		"Previous"	 :""
	}
	var closed_list = {};
	do
	{
		
		// determine the path which we assume is mose efficient to our end point
		// this is a very inefficient method, but it should work for proof of concept
		var current = "";
		var shortest_sum = null;
		for (name in open_list)
		{
			if (shortest_sum == null)
			{
				current = name;
			}
			else
			{
				if (open_list[name].Sum < shortest_sum)
				{
					current = name;
				}
			}
		}
		
		// move the tile to the closed list
		closed_list[current] = open_list[current];
		delete open_list[current];
		// check each neighbour
		for (var i = 0; i < tiles[current].neighbours.length; i++)
		{
			var neighbour_name = tiles[current].neighbours[i];
			// allready seen
			if (neighbour_name in closed_list)
			{
				continue;
			}
			// impassable terrain
			if ( tiles[neighbour_name].type == 0 )
			{
				continue;
			}
			// check if the path may use the terrain type
			if ( (tiles[neighbour_name].type & legal) != tiles[neighbour_name].type)
			{
				continue;
			}
			
			// create the AStar information object for the neighbour
			var neighbour = 
			{
					"Cost"	      : closed_list[current].Cost + 10000,
					// we should be able to not squareroot, because it will not change anything
					"AssumedRest" : Math.abs(tiles[neighbour_name].center[0] - tiles[end].center[0])
									+ Math.abs(tiles[neighbour_name].center[1] - tiles[end].center[1]),
					//"Sum"		  : this.Cost + this.AssumedRest,
					"Previous"	  : current
			};
			neighbour["Sum"] = neighbour.Cost + neighbour.AssumedRest;
			// we reached this tile from another path before
			if (neighbour_name in open_list)
			{
				// check if this path is better
				if (neighbour.Cost < open_list[neighbour_name].Cost)
				{
					// if so, replace the path
					open_list[neighbour_name] = neighbour;
				}
			}
			else
			{
				open_list[neighbour_name] = neighbour;
			}
		}
	} while (current != end && getAssocArrayLength(open_list) > 0)

	if (current == end)
	{
		var result = Array();
		do
		{
			result.push(current);
			alert(closed_list[current].Sum);
			current = closed_list[current].Previous;
		} while(current != start);
		result.reverse();
		return result;
	}
	return false;
}
/** Finds a path from start to end.
 * 
 * This is my replacement function for the A* algorithm which is not applicable
 * to the irregular A&A map, because there is no satisfying way to do the needed
 * heuristic.
 * 
 * Since heuristic is not possible and each edge in our graph has a fixed weight 
 * of 1 we are stuck with brute forcing our path. The algorithm will traverse possible
 * paths in level of delpth steps, starting at 0 and iterating deeper into the field. Tiles
 * that where already visited in a lower level of depth are skipped and not checked again.
 * This will produce the shortest path to end from start. It may however take long because
 * there is no logic to speed things up.
 * 
 * @param tiles
 * @param start
 * @param end
 * @param legal
 * @param max_lod
 * @return
 */
function findPath(tiles, start, end, legal, max_lod)
{
	var lod = 0;
	var visited = { };
	var search_graph = [ {} ];
	var current = start;
	search_graph[lod][current] = true;
	visited[current] = true;
	do
	{
		// add new lod
		search_graph.push( {} );
		for (var node in search_graph[lod])
		{
			current = node;
			// look at each neighbour
			for (var i = 0; i < tiles[current].neighbours.length; i++)
			{
				var neighbour = tiles[current].neighbours[i];
				
				if (! (neighbour in visited))
				{
					visited[neighbour] = true;
					if (tiles[neighbour].type == 0)
					{
						//console.log('pathfinding found impassable terrain at ' + neighbour);
						continue;
					}
					if ((tiles[neighbour].type & legal) != tiles[neighbour].type)
					{
						//console.log('pathfinding found illegal terrain at ' + neighbour);
						//console.log('legal: ' + legal + '\ntype: ' + tiles[neighbour].type + '\nbitwise: ' + (tiles[neighbour].type & legal));
						continue;
					}		
					search_graph[lod+1][neighbour] = current;
				}
			}
			if (current == end)
			{
				var result = [];
				do
				{
					result.push(current);
					current = search_graph[lod][current];
				} while(lod--)
				result.reverse();
				return result;
			}
		}
		lod ++;
	} while( (max_lod > lod) && (getAssocArrayLength(search_graph[lod]) > 0 ));

	return false;
}
