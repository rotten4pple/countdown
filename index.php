<html >
<title >Countdown Numbers Problem</title>
<link rel="stylesheet" type="text/css" href="./countdown.css" ></link>
<body >

<h1 >Countdown Numbers Problem</h1>

<?php

// Temp: dump the posted values.
//var_dump( $_POST );

// See if the form was submitted and all values were specified. The the form wasn't submitted, return
// and "empty" array. If it was and there were blanks, generate the gaps and return the values.
// The method returns flags to say whether the form was subitted and any fields were left blank.
// If not submitted, or submitted with gaps, we don't process the form, we just re-display it.
$form_values = fill_in_gaps();

?>

<form method="post" action="" >
<h2 >Enter the Target, or leave blank for the computer to choose</h2>

<input type="text" name="target_number" maxlength="3" class="number" value="<?php echo $form_values['target']; ?>" />

using
<select name="top_line" >
<?php
for( $tlidx = 0 ; $tlidx < 4 ; $tlidx++ ) {
    // Note: $form_values['top_line'] will always be populated, so we don't have to test if it's set.
    $selected = ( $tlidx == $form_values['top_line'] ? 'selected="selected" ' : '' );
    echo '<option value="' . $tlidx . '" ' . $selected . '>' . $tlidx . '</option>';
}
?>
</select>
from the top line.

<h2 >Enter the 6 numbers, or leave blank for the computer to choose</h2>

<?php

for( $i = 0 ; $i < 6 ; $i++ ) {
    echo ( 1 + $i ) . ': <input type="text" name="given_number[' . $i . ']" maxlength="3" class="number" value="' . $form_values['user_numbers'][$i] . '" /> ';
}

$allowed_debug_levels = array(
    0 => 'None',
    3 => 'A nice amount',
    4 => 'Full details of best solution',
    5 => 'Calculations being excluded - be careful',
    8 => 'Details of every calculation - be very, very careful'
);
echo '<p >';
echo 'Select the debug level: <select name="debug_lvl" >';
foreach( $allowed_debug_levels as $allowed_debug_level => $level_description ) {
    $selected = ( $allowed_debug_level == $form_values['debug_level'] ? 'selected="selected" ' : '' );
    echo '<option value="' . $allowed_debug_level . '" ' . $selected . '>' . $allowed_debug_level . ' - ' . $level_description . '</option>';
}
echo '</select>';
echo '</p>';

?>

<p >
    <input type="submit" name="go" value="Go" />
    <input type="submit" name="clear" value="Clear" />
</p>

<?php

// After after displaying the form, determine whether it was submitted, and if so whether we filled in any gaps.
// We only process the form if submitted with all numbers present.
if ( $form_values['submitted'] ) {
    if ( $form_values['any_gaps'] ) {
        echo '<p >The gaps were filled in with random numbers. Press Go to process...</p>';
    } else {
        // Validate the entries.
        if ( $form_values['top_line'] == $form_values['from']['top'] ) {
            if ( 0 == $form_values['from']['nowhere'] ) {
                // All valid, so process the form.
                process_form();
            } else {
                echo '<p class="error" >Error: ' . $form_values['from']['nowhere'] . ' number(s) is/are invalid.</p>';
            }
        } else {
            echo '<p class="error" >Error: Incorrect number of numbers from the top line.</p>';
        }
    }
}

?>

</form>

</body>
</html>

<?php

function fill_in_gaps() {
    // Initialise the return value (array). We ovewrite it with either entered values or random ones.
    $form_fields = array();
    $form_fields['target'] = NULL;
    $form_fields['top_line'] = 1;  // Default to 1 from the top line.
    $form_fields['from'] = array( 'top' => 0, 'anywhere' => 0, 'nowhere' => 0 );  // Records where the numbers come from.
    $form_fields['user_numbers'] = array();
    for( $i = 0 ; $i < 6 ; $i++ ) {
        $form_fields['user_numbers'][$i] = NULL;
    }
    $form_fields['debug_level'] = 3;  // Default to 3, as it's a nice level.
    $form_fields['submitted'] = FALSE;
    $form_fields['any_gaps'] = FALSE;
    // If user clicked "clear", we stop at this point.
    if ( ! array_key_exists( 'clear', $_POST ) ) {
        if ( array_key_exists( 'go', $_POST ) ) {
            $form_fields['submitted'] = TRUE;
        }
        
        if ( array_key_exists( 'target_number', $_POST ) && '' != $_POST['target_number'] ) {
            $form_fields['target'] = $_POST['target_number'];
        } else {
            $form_fields['any_gaps'] = TRUE;
            $form_fields['target'] = rand( 100, 999 );
        }
        $form_fields['top_line'] = ( array_key_exists( 'top_line', $_POST ) ? $_POST['top_line'] : '1' );
        
        // We need to count how many of the numbers submitted in the form are in the set 1-10, and
        // how many are 25, 50, 75 or 100. We also count how many are in neither!
        // Later, if we generate numbers, we need to make sure the target number of numbers from each
        // place is hit.
        if ( array_key_exists( 'given_number', $_POST ) ) {
            for( $i = 0 ; $i < 6 ; $i++ ) {
                if ( array_key_exists( $i, $_POST['given_number'] ) && '' != $_POST['given_number'][$i] ) {
                    $this_number = $_POST['given_number'][$i];
                    // Increment the appropriate counter.
                    if ( 25 == $this_number || 50 == $this_number || 75 == $this_number || 100 == $this_number ) {
                        $form_fields['from']['top']++;
                    } elseif ( 1 <= $this_number && 10 >= $this_number ) {
                        $form_fields['from']['anywhere']++;
                    } else {
                        $form_fields['from']['nowhere']++;
                    }
                }
            }
        }
        
        for( $i = 0 ; $i < 6 ; $i++ ) {
            if ( array_key_exists( 'given_number', $_POST ) &&
                 array_key_exists( $i, $_POST['given_number'] ) &&
                 '' != $_POST['given_number'][$i] ) {
                $form_fields['user_numbers'][$i] = $_POST['given_number'][$i];
            } else {
                $form_fields['any_gaps'] = TRUE;
                // User number is blank. We want either a number in the set { 25, 50, 75, 100 }
                // or a number between 1 and 10. The user's choice of how many numbers to take from
                // the top line, and the number we already have from the top line, determine which
                // set we take the number from.
                if ( $form_fields['from']['top'] < $form_fields['top_line'] ) {
                    $form_fields['user_numbers'][$i] = 25 * rand( 1, 4 );
                    $form_fields['from']['top']++;
                } else {
                    $form_fields['user_numbers'][$i] = rand( 1, 10 );
                    $form_fields['from']['anywhere']++;
                }
            }
        }
        
        $form_fields['debug_level'] = ( array_key_exists( 'debug_lvl', $_POST ) ? $_POST['debug_lvl'] : '3' );
    }
    
    return $form_fields;
}

// This is the guts of the page. It takes the 6 given numbers and tries to
// find ways of combining them to reach the target number.
function process_form() {
    
    require_once( './CountdownProblem.php' );
    $solution = new CountdownProblem( $_POST['target_number'], $_POST['given_number'] );
    $use_level = ( isset( $_POST['debug_lvl'] ) && 0 <= $_POST['debug_lvl'] ) ? $_POST['debug_lvl'] : 0;
    $solution->solve2( $use_level );
    
    // Okay, let's see what we've ended up with.
    echo '<p >';
    if ( $solution->getBestAnswer() == $_POST['target_number'] ) {
        echo 'Bingo! We hit the target!';
    } else {
        echo 'We missed the target by ' . $solution->getMissedTargetByAmount();
    }
    echo '</p>';
    
    echo '<p >';
    echo 'Solution: ' . $solution->getBestSolutionAsString();
    echo '</p>';
    
    echo '<p >';
    echo 'Time taken: ' . $solution->getTimeTakenSeconds() . ' seconds.';
    echo '<br />';
    echo 'Expressions evaluated: ' . $solution->getCallCount();
    echo '</p>';
    
}


?>