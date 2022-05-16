/**
* ImageResize
*
* ImageResize.js is (c) 2011 Lemax d.o.o.
* http://www.lemax.net/
*
*/
(function(window, undefined) {
	ImageResize = function() { }
	ImageResize.bestFitInside = function(destWidth, destHeight, image, dontScaleUp, align) {
		//	Ovu sliku koristim zbog toga jer IE ne oæitava dobro dimenzije ako je display:none
		var im = new Image();
		im.src = image.src;
		image.style.display = "block";
		image.style.visibility = "visible";
		if (dontScaleUp == true) {
			if (im.width < destWidth || im.height < destHeight) {
				return;
			}
		}
		var destRatio = destWidth / destHeight;
		var targetRatio = im.width / im.height;
		if (destRatio == targetRatio) {
			image.style.width = destWidth + "px";
			image.style.height = destHeight + "px";
			return;
		}
		if (destRatio > targetRatio) {
			var w = parseInt(destHeight * targetRatio);
			image.style.width = w + "px";
			image.style.height = destHeight + "px";
			switch (align) {
				case "left":
					image.style.left = "0";
					break;
				case "right":
					image.style.right = "0";
					break;
				default:
					image.style.left = parseInt((destWidth - w) / 2) + "px";
					break;
			}
			return;
		}
		var h = parseInt(destWidth / targetRatio);
		image.style.width = destWidth + "px";
		image.style.height = h + "px";
		switch (align) {
			case "top":
				image.style.top = "0";
				break;
			case "bottom":
				image.style.bottom = "0";
				break;
			default:
				image.style.top = parseInt((destHeight - h) / 2) + "px";
				break;
		}
	};
	ImageResize.bestFitOutside = function(destWidth, destHeight, image, dontScaleUp, align) {
		//	Ovu sliku koristim zbog toga jer IE ne oæitava dobro dimenzije ako je display:none
		var im = new Image();
		im.src = image.src;
		image.style.display = "block";
		image.style.visibility = "visible";
		if (dontScaleUp == true) {
			if (im.width < destWidth || im.height < destHeight) {
				return;
			}
		}
		var destRatio = destWidth / destHeight;
		var targetRatio = im.width / im.height;
		if (destRatio == targetRatio) {
			image.style.width = destWidth + "px";
			image.style.height = destHeight + "px";
			return;
		}
		if (destRatio < targetRatio) {
			var w = parseInt(destHeight * targetRatio);
			image.style.width = w + "px";
			image.style.height = destHeight + "px";
			switch (align) {
				case "left":
					image.style.left = "0";
					break;
				case "right":
					image.style.right = "0";
					break;
				default:
					image.style.left = parseInt((destWidth - w) / 2) + "px";
					break;
			}
			return;
		}
		var h = parseInt(destWidth / targetRatio);
		image.style.width = destWidth + "px";
		im.style.height = h + "px";
		switch (align) {
			case "top":
				image.style.top = "0";
				break;
			case "bottom":
				image.style.bottom = "0";
				break;
			default:
				image.style.top = parseInt((destHeight - h) / 2) + "px";
				break;
		}
		return;
	};
	window.ImageResize = ImageResize;
})(window);

