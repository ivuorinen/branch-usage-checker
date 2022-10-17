<?php

test('check command', function () {
    $this->artisan('check ivuorinen branch-usage-checker')
      // ->expectsOutput('')
         ->assertExitCode(0);
});
