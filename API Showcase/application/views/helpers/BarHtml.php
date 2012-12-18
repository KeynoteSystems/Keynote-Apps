<?php
class Zend_View_Helper_BarHtml
{
    public function barHtml($values, $color='black', $width=200)
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

        $out = "<table cellspacing='5'>" . PHP_EOL;

        foreach($values as $k => $v)
        {
            $bar_h = abs(round($v * $kf));
            $out .= "<tr>" .PHP_EOL;
            $out .= "<td><strong>{$k}</strong></td>" .PHP_EOL;
            $out .= "</tr>" .PHP_EOL;
            $out .= "<tr>" .PHP_EOL;
            $out .= "<td width='{$width}px' align='left' style='border-left: {$bar_h}px solid {$color};'>&nbsp;{$v}</td>" .PHP_EOL;
            $out .= "</tr>" .PHP_EOL;

        }

        $out .= '</table>' .PHP_EOL ;

        return $out;
    }
}