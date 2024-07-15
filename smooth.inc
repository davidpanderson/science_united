<?php

// the SU accounting data has (for unknown reasons)
// occasional outlier values, 10X or 20X the nearby averages.
// This class replaces these with plausible values.

class SMOOTHER {
    private $avg = 0;
    private $first = true;
    function val($x) {
        if (!$x) return 0;
        if ($this->first) {
            $this->avg = $x;
            $this->first = false;
            return $x;
        }
        if ($x > 1.3*$this->avg) {
            $this->avg *= 1.01;
            return $this->avg;
        }
        $this->avg = .9*$this->avg + .1*$x;
        return $x;
    }
}

?>
