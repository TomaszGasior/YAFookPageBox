<?php

namespace YAFookPageBox;

class FacebookPageBox
{
	const TAB_NONE     = 0b000000;
	const TAB_TIMELINE = 0b000001;
	const TAB_EVENTS   = 0b000010;
	const TAB_MESSAGES = 0b000100;

	private $_fanpageURL  = null;
	private $_boxHeight   = 400;
	private $_shownTabs   = self::TAB_TIMELINE;
	private $_showCover   = true;
	private $_showFriends = true;
	private $_showCTA     = false;
	private $_smallHeader = false;

	private $_CSSClass     = 'yafook-page-box';
	private $_loadingDelay = 2000;

	public function getFanpageURL() : ?string
	{
		return $this->_fanpageURL;
	}

	public function setFanpageURL(string $fanpageURL) : void
	{
		if (!filter_var($fanpageURL, FILTER_VALIDATE_URL) or strpos($fanpageURL, 'facebook.com') === false) {
			throw new FacebookPageBoxException("Fanpage URL \"$fanpageURL\" is invalid.");
		}

		$this->_fanpageURL = $fanpageURL;
	}

	public function getHeight() : int
	{
		return $this->_boxHeight;
	}

	public function setHeight(int $height) : void
	{
		if ($height < 70) {
			throw new FacebookPageBoxException('Height must be greater than 70 px.');
		}

		$this->_boxHeight = $height;
	}

	public function getShownTabs() : int
	{
		return $this->_shownTabs;
	}

	public function setShownTabs(int $shownTabs) : void
	{
		$this->_shownTabs = $shownTabs;
	}

	public function getShowCover() : bool
	{
		return $this->_showCover;
	}

	public function setShowCover(bool $enabled) : void
	{
		$this->_showCover = $enabled;
	}

	public function getShowFriends() : bool
	{
		return $this->_showFriends;
	}

	public function setShowFriends(bool $enabled) : void
	{
		$this->_showFriends = $enabled;
	}

	public function getShowCTA() : bool
	{
		return $this->_showCTA;
	}

	public function setShowCTA(bool $enabled) : void
	{
		$this->_showCTA = $enabled;
	}

	public function getSmallHeader() : bool
	{
		return $this->_smallHeader;
	}

	public function setSmallHeader(bool $enabled) : void
	{
		$this->_smallHeader = $enabled;
	}

	public function getCSSClass() : string
	{
		return $this->_CSSClass;
	}

	public function setCSSClass(string $class) : void
	{
		$this->_CSSClass = $class;
	}

	public function getLoadingDelay() : int
	{
		return $this->_loadingDelay;
	}

	public function setLoadingDelay(int $delayMS) : void
	{
		if ($delayMS < 0) {
			throw new FacebookPageBoxException('Delay must be greater than or equal to 0 ms.');
		}

		$this->_loadingDelay = $delayMS;
	}

	public function __toString() : string
	{
		ob_start();
		$this->render();
		return ob_get_clean();
	}

	public function render() : void
	{
		if (!$this->_fanpageURL) {
			throw new FacebookPageBoxException('Fanpage URL is not specified.');
		}

		$generateBoolString = function(bool $value) : string
		{
			if ($value === true) {
				return 'true';
			}
			if ($value === false) {
				return 'false';
			}
		};
		$generateTabsString = function(int $value) : string
		{
			$tabs = [];

			if ($value & self::TAB_TIMELINE) {
				$tabs[] = 'timeline';
			}
			if ($value & self::TAB_EVENTS) {
				$tabs[] = 'events';
			}
			if ($value & self::TAB_MESSAGES) {
				$tabs[] = 'messages';
			}

			return implode(',', $tabs);
		};

		$facebookURL = 'https://www.facebook.com/plugins/page.php?' . http_build_query([
			'href'          => $this->_fanpageURL,
			'height'        => $this->_boxHeight,
			'tabs'          => $generateTabsString($this->_shownTabs),
			'hide_cover'    => $generateBoolString(!$this->_showCover),
			'show_facepile' => $generateBoolString($this->_showFriends),
			'hide_cta'      => $generateBoolString(!$this->_showCTA),
			'small_header'  => $generateBoolString($this->_smallHeader),
		]);

		$this->_outputCode([
			'widgetURL'    => $facebookURL,
			'height'       => $this->_boxHeight,
			'CSSClass'     => $this->_CSSClass,
			'loadingDelay' => $this->_loadingDelay,
		]);
	}

	private function _outputCode(array $variables) : void
	{
		extract($variables);

?>
<div class="<?= $CSSClass ?>" style="max-width: 500px; overflow-x: hidden; font-size: 0">
	<iframe style="border: none; height: <?= $height ?>px"></iframe>
</div>

<script>
(function(){
	var container = document.querySelector('div.<?= $CSSClass ?>'),
	    iframe    = container.querySelector('div.<?= $CSSClass ?> > iframe'),
	    baseURL   = '<?= $widgetURL ?>',
	    lastWidth = null,
	    timeoutId = null;

	function updateFacebookFrame()
	{
		var width = container.clientWidth;

		if (lastWidth != null && Math.abs(width - lastWidth) < 10) {
			return;
		}

		if (width < 180) {
			width = 180;
		}
		else if (width > 500) {
			width = 500;
		}

		iframe.style.width = width + 'px';
		iframe.src = baseURL + '&width=' + width;

		lastWidth = width;
	}

	function setup()
	{
		updateFacebookFrame();

		window.addEventListener('resize', function(){
			clearTimeout(timeoutId);
			timeoutId = setTimeout(updateFacebookFrame, 400);
		});
	}
	setTimeout(setup, <?= $loadingDelay ?>);
})();
</script>
<?php
	}
}

class FacebookPageBoxException extends \Exception {}