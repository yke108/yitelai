var FlotPyramid=(function(){var InvalidData={plugin:'flot.pyramid',msg:'Invalid series for pyramid plot! The supplied data must have exactly the same labels!'},InvalidDirection={plugin:'flot.pyramid',msg:'Invalid direction specified for pyramid series. Use \'L\' or \'W\' for left, or \'R\' or \'E\' for right (default is right)'}
function init(plot){plot.hooks.processOptions.push(processOptions);}
function processOptions(plot,options){plot.pyramidYaxisTicks=[];if(options.series.pyramid&&options.series.pyramid.show){$.extend(options.series.bars,{show:true,horizontal:true,align:'center',barWidth:options.series.pyramid.barWidth||0.6});var xaxis=options.xaxes[options.series.xaxis- 1||0];$.extend(xaxis,{tickFormatter:xaxisTickFormatter(xaxis.tickFormatter)});var yaxis=options.yaxes[options.series.yaxis- 1||0];$.extend(yaxis,{ticks:plot.pyramidYaxisTicks});plot.hooks.processRawData.push(processRawData);plot.hooks.processDatapoints.push(processDatapoints);}}
function processRawData(plot,series,datapoints){series.data=$.extend(true,[],series.data);fixYaxis(series.data,plot.pyramidYaxisTicks);fixXaxis(plot.getOptions(),series);}
function processDatapoints(plot,series,datapoints){var swapped=[],points=datapoints.points,mult=(series.pyramid.direction||'R').match(/L|W/)?-1:1;for(var i=0,len=points.length;i<len;i+=datapoints.format.length){swapped.push(points[i+1]*mult);swapped.push(points[i]);swapped.push(points[i+2]);}
datapoints.points=swapped;}
function xaxisTickFormatter(oldFormatter){return function(val,axis){val=val<0?-val:val;return oldFormatter?oldFormatter(val,axis):val;}}
function fixXaxis(options,series){var max,currentMax=options.xaxes[0].max||0,data=series.data,values;checkSeriesDirection(series);function reduce(data,f){return data.reduce(function(prev,current,index,array){return f(prev,current);});}
values=jQuery.map(data,function(d){return d[1]});max=values[0];for(i=0;i<values.length;i++){if(values[i]>max){max=values[i]};}
options.xaxes[0].max=Math.max(max,currentMax);options.xaxes[0].min=-options.xaxes[0].max;}
function checkSeriesDirection(series){var direction=series.pyramid.direction;if(direction&&!direction.match(/L|W|R|E/)){throw(InvalidDirection);}}
function fixYaxis(data,yaxisTicks){if(yaxisTicks.length==0){extractTicks(data,yaxisTicks);}else{checkTicks(data,yaxisTicks);}
rewriteTicks(data);}
function extractTicks(data,yaxisTicks){var i,len;for(i=0,len=data.length;i<len;i+=1){yaxisTicks.push([i,data[i][0]]);}}
function checkTicks(data,yaxisTicks){if(!sameTicksLength(data,yaxisTicks)||!allTicksPresent(data,yaxisTicks)){throw(InvalidData);}}
function sameTicksLength(data,yaxisTicks){return yaxisTicks.length==data.length;}
function allTicksPresent(data,yaxisTicks){var labels,expected_labels;expected_labels=$.map(yaxisTicks,function(e){return e[1]});labels=$.map(data,function(e){return e[0]});return expected_labels.toString()==labels.toString();}
function rewriteTicks(data){for(var i=0,len=data.length;i<len;i+=1){data[i][0]=i;}}
return{init:init,InvalidDirection:InvalidDirection,InvalidData:InvalidData}}());(function($){$.plot.plugins.push({init:FlotPyramid.init,name:"pyramid",version:"1.0.2"});})(jQuery);