<?php
declare(strict_types=1);

if (!class_exists('PEAR_ErrorStack', false)) {
    class PEAR_ErrorStack {
        /** @return self */
        public static function singleton() {
            return new self();
        }
    }
}

