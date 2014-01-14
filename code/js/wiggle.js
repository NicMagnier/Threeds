var wiggle = new function() {
	// can touch
	this.speed = 200;
	this.smooth = true;
	if ( navigator.userAgent.indexOf('Nintendo 3DS')!=-1 )
		this.smooth = false;
	
	// Don' touch
	this.play = false;
	this.showControls = false;

	// init the class
	this.init = function() {
		// check if we have to show the controls
		e = $('.wiggleFront');
		if ( e.length>0 ) {
			$('#wiggleControl').show();
			this.showControls = true;
		}
		
		// get cookies
		var ca = document.cookie.split(';');
		var s1 = "wiggleSpeed=";
		var s2 = "wiggleSmooth=";
		for(var i=0; i < ca.length; i++) {
			var c = ca[i];

			while (c.charAt(0)==' ')
				c = c.substring(1,c.length);

			if (c.indexOf(s1) == 0) {
				wiggle.setSpeed(c.substring(s1.length,c.length));
				s1 = null;
			}
			else if (c.indexOf(s2) == 0) {
				wiggle.setSmooth(c.substring(s2.length,c.length));
				s2 = null;
			}
		}
		
		if ( s1!=null )	wiggle.setSpeed(this.speed);
		if ( s2!=null )	wiggle.setSmooth(this.smooth);
	}
	
	// start the animation
	this.start = function() {
		if (wiggle.play) return;
		wiggle.play = true;
		wiggle.update();
	}
	
	// switch off all the animations
	this.stop = function() {
		wiggle.play = false;
	}
	
	this.update = function() {
		if (!wiggle.play) return;
		
		e = $('.wiggleFront');
		
		f = $(e).first();
		if ( f.hasClass('_hide') ) {
			e.show();
			if (wiggle.smooth)
				e.animate({opacity: 1}, wiggle.speed*0.7);
			else
				e.css({opacity: 1});
				
			f.removeClass('_hide');
		}
		else {
			if (wiggle.smooth) {
				e.animate({opacity: 0}, wiggle.speed*0.7);
			}
			else {
				e.css({opacity: 1});
				e.hide();
			}
				
			f.addClass('_hide');
		}

		setTimeout("wiggle.update();", wiggle.speed);
	}
	
	this.setSpeed = function(s) {
		wiggle.speed = s;
		if ( s==0 )
			wiggle.stop();
		else
			wiggle.start();

		var date = new Date();
		date.setTime(date.getTime()+(365*24*60*60*1000));
		document.cookie = "wiggleSpeed="+s+"; expires="+date.toGMTString()+"; path=/";
		
		wc = $('#wiggleControl');
		$('.wcSpeed', wc).removeClass('wcSpeedSelected');
		$('.wcSpeed'+s, wc).addClass('wcSpeedSelected');
	}
	
	this.setSmooth = function(b) {
		if ( b==true || b=='true' )
			b = true;
		else
			b = false;
		wiggle.smooth = b;
		
		var date = new Date();
		date.setTime(date.getTime()+(365*24*60*60*1000));
		document.cookie = "wiggleSmooth="+b+"; expires="+date.toGMTString()+"; path=/";

		wc = $('#wiggleControl');
		$('.wcSmooth', wc).prop("checked", b);
	}
}

// manage various interactions
$(document).ready(function() {
	// to avoid that the text is selected when the user double click
	$('.psub').mousedown(function(){return false;});
	$('.padd').mousedown(function(){return false;});
	$('.psub').on("click", function(e){
		var i = $(this).next();
		$(i).val($(i).val()-1);
		$(i).trigger('change');
	});
	$('.padd').on("click", function(e){
		var i = $(this).prev();
		$(i).val(parseInt($(i).val(),10)+1);
		$(i).trigger('change');
	});
	
	// Save behavior
	$('.menuSave')
		.click(function() {
			var s = $('.saving', this);
			if ( s.is(":visible") ) return;
			
			saveImage(s);
			$(this).children().hide();
			$(s).show();
		})
		.hide()
		.children().hide();
	$('.menuSave .save').show();
	
	// "focus" event is for the 3DS
	if ( navigator.userAgent.indexOf('Nintendo 3DS')!=-1 )
		evt = 'focus';
	else
		evt = 'change keyup';
		
	$('.description textarea').on(evt, function() {
		setSaveDirty(this);
	});
});
$(document).on("change keyup", 'input.parallax', function(){
	var v = parseInt($(this).val());
	if ( isNaN(v) ) return;
	imgSetParallax($(this).parents('.postContainter').find('.wiggleContainer'), pixel2ratio(v));
});

function ratio2pixel(plx) {
	return parseInt((plx-1.0)*-640);
}

function pixel2ratio(plx) {
	return (640-plx)/640.0;
}

// e: html element
// p: parallax
// we assume this is a jps, for mpo support see previous versions
function imgWiggle(e,p) {
	if ( e.tagName=="IMG" )
		url = e.src;
	else
		console.log(e);

	// image informations
	width = $(e).width();
	height = $(e).height();
	
	// ups is two images side by side
	width/= 2;
	
	// On 3DS image has to be reduced to fit the screen (320px) 
	if ( navigator.userAgent.indexOf('Nintendo 3DS')!=-1 && $(e).hasClass('scaleFor3DS') ) {
		width/= 2;
		height/= 2;
	}

	// first we create the div to wiggle
	container = $('<div/>', {width: width})
		.addClass('wiggleContainer')
		.hide();
	
	backDiv = $('<div/>', {
			height: height,
		})
		.css({'background-image': 'url('+url+')', 'background-size':(width*2)+'px '+height+'px'})
		.addClass('wiggleBack')
		.appendTo(container);
	
	frontDiv = $('<div/>', {
			height: height,
		})
		.css({'background-image': 'url('+url+')', 'background-size':(width*2)+'px '+height+'px'})
		.addClass('wiggleFront')
		.appendTo(container);
	
	// an empty div on top of the images.
	// because #wiggleFront is toggle quickly, the click event (triggered by a mousedown, mouse up on the same element) are not always fired (
	eventDiv = $('<div/>', {
			height: height,
		})
		.addClass('wiggleEvent')
		.appendTo(container);
	
	$(e).after(container);
	container.data('width', width);
	container.data('height', height);
	container.data('parallax', p);
	
	imgUpdate(container);
	
	// set the parallax input field
	$(container)
		.parents('.postContainter')
		.find('input.parallax')
		.val( ratio2pixel(p));

	$(e).hide();
	$(container).show();
	
	// the script worked so far and replaced standard <img>
	$('body').removeClass('noWiggleScript');
}

function imgSetParallax(e,p) {
	if (p==0) {
		// stop wiggling
	}
	
	e.data('parallax', p);
	setSaveDirty(e);
	imgUpdate(e);
}

function imgUpdate(e) {
	w = e.data('width');
	h = e.data('height');
	p = e.data('parallax');
	
	if ( w==0 ) {
		getWidthAgain(e.prev());
		return;
	}

	$(e).height(h);
	cw = w*p; // width of the wiggle picture
	if (p<1.0) {
		$(e).width(cw);
		$('.wiggleBack', e)
			.width(cw)
			.css({
				'background-position': 'left top',
				'left': '0'
			});
		$('.wiggleFront', e)
			.width(cw)
			.css({
				'background-position': 'right top',
				'right': '0'
			});
	}
	else {
		mw = w*(p-1.0); // border margin width
		$(e).width(cw);
		$('.wiggleBack', e)
			.width(cw-(mw*2))
			.css({
				'background-position': '-'+mw+'px 0px',
				'left': mw+'px'
			});
		$('.wiggleFront', e)
			.width(cw-(mw*2))
			.css({
				'background-position': '-'+w+'px 0px',
				'right': mw+'px'
			});
	}
}

function getWidthAgain(e) {
	$("<img/>") // Make in memory copy of image to avoid css issues
	.attr("src", $(e).attr("src"))
	.data("cid", $(e).parent().attr('id'))
	.load(function() {
		var cid = $(this).data('cid');
		var c = $('#'+cid+'.jps').find('.wiggleContainer');
		var w = this.width; // Note: $(this).width() will not work for in memory images.
		if ( this.src.slice(-3).toLowerCase()=='jps')
			w = Math.round(w/2);

		$(c).data('width', w); 
		$(c).data('height', this.height);
		imgUpdate(c);
	});
}

function pfocus(event, elm, gid) {
	var hid = $(elm).attr('id');
	
	if(typeof event.offsetX === "undefined" || typeof event.offsetY === "undefined") {
	   var to = $(event.target).offset();
	   var x = event.pageX - to.left;
	   var y = event.pageY - to.top;
	}
	else {
		var x = event.offsetX;
		var y = event.offsetY;
	}
//	var x = event.offsetX?(event.offsetX):event.pageX-elm.offsetLeft;
//	var y = event.offsetY?(event.offsetY):event.pageY-elm.offsetTop;
	
	$('<img/>')
		.attr("src", $(LoadAnimation).attr("src"))
		.addClass('loadanim')
		.css({
			'position': 'absolute',
			'top': (y-8)+'px',
			'left': (x-8)+'px',
		})
		.appendTo(elm);
	
	imgw = $('img', elm).width() / 2;
	imgh = $('img', elm).height();
	x+= imgw - $(elm).width();
	x/= imgw;
	y/= imgh;
	
	$.ajax({
	  url: "ajax.focus",
	  type: "POST",
	  data: "gid="+gid+"&hid="+hid+"&x="+x+"&y="+y,
	  dataType: "json",
	  success: function(json) {
	  	animPF(json.hid, json.parallax);
//	  	imgSetParallax($('.wiggleContainer', '#'+json.hid), json.parallax);
	  }
	});
}

function animPF(hid, p) {
	e = $('.wiggleContainer', '#'+hid);
	e.data('target', p);
	
	par = e.data('parallax');
	if ( p==par ) {
	  	$('.loadanim', '#'+hid).remove();
		return;
	}

	s = (p-par)/20;
	e.data('step', s);
	
	updatePF(hid);
}

function updatePF(hid) {
	e = $('.wiggleContainer', '#'+hid);
	tar = e.data('target');
	stp = e.data('step');
	par = e.data('parallax');
	d = tar-par;
	
	if ( stp*stp > d*d )
	{
	  	imgSetParallax(e, tar);
		e.removeData('target');
		e.removeData('step');
	  	$('.loadanim', '#'+hid).remove();
		return;
	}
	
  	imgSetParallax(e, par+stp);
  	
	// set the parallax input field
	$(e).parents('.postContainter')
		.find('input.parallax')
		.val( ratio2pixel(p));
  	
	setTimeout("updatePF('"+hid+"');", 33);
}


// -----------
// Save system
// -----------

function setSaveDirty(e) {
	var se = $(e).parents('.postContainter').find('.menuSave');
	se.addClass('dirty');
	se.show(300);
}

function saveImage(e) {
	e = $(e).parents('.postContainter');
	var gid = e.attr('id');
	var p = $('.wiggleContainer',e).data('parallax');
	var d = encodeURIComponent($('.description textarea',e).val());
	
	var request = $.ajax({
		url: "ajax.save",
		type: "POST",
		data: "gid="+gid+"&parallax="+p+"&description="+d,
		dataType: "json",
//	    dataFilter: function(data, dataType) { console.log(data); return data; }
	});
	
	request.done(function(json) {
		var s = $('#'+json.gid+' .menuSave');
		s.removeClass('dirty');
		$('.saving', s)
			.hide()
			.siblings('.saved').show();
		setTimeout( 'closeSave("'+json.gid+'")', 2000);
	});
	
	request.fail(function(jqXHR, textStatus) {
	  console.log( "Request failed: " + textStatus );
	});
}

function closeSave(gid) {
	var e = $('#'+gid+' .menuSave');
	
	if ( $('.saving', e).is(":visible") ) return;
	
	e.children().hide();
	$('.save', e).show();
	
	if ( e.hasClass('dirty') ) return;
	
	e.hide(300);
}
