<?php
class Zend_View_Helper_BarHtml
{
	public function barHtml($values, $color = 'black', $width = 200)
	{
		$max = -1;

		foreach($values as $k => $v) {
			if (abs($v) > $max) {
				$max = abs($v);
			}
		}

		if ($max != 0) {
			$kf = $width / $max;
		} else {
			$kf = 0;
		}

		$out = '';

		foreach($values as $k => $v) {
		    $k = htmlspecialchars($k);

		    $bar_h = abs(round($v * $kf));

			$out .= "<table cellspacing='8' border='0' cellpadding='0' width='{$width}' style='font-family: Arial, Helvetica, sans-serif;color: #FFFFFF; font-size: 12px'>" . PHP_EOL;
			$out .= "<tr>" .PHP_EOL;
			$out .= "<td><b>{$k}</b></td>" .PHP_EOL;
			$out .= "</tr>" .PHP_EOL;
			$out .= "</table>";
			$out .= "<table cellspacing='3' border='0' cellpadding='0' style='font-family: Arial, Helvetica, sans-serif;color: #FFFFFF; font-size: 11px; line-height:11px'>";
			$out .= "<tr>" .PHP_EOL;
			$out .= "<td width='{$bar_h}' bgcolor='{$color}'>&nbsp;</td><td>&nbsp;{$v}</td>" .PHP_EOL;
			$out .= "</tr>" .PHP_EOL;
			$out .= "</table>";
		}

		return $out;
	}
}