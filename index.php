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

?>

<p >
    <input type="submit" name="go" value="Go" />
    <input type="submit" name="clear" value="Clear" />
</p>

<?php
// TODO: Add an option to clear the form.

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
        $form_fields['top_line'] = $_POST['top_line'];
        
        // We need to count how many of the numbers submitted in the form are in the set 1-10, and
        // how many are 25, 50, 75 or 100. We also count how many are in neither!
        // Later, if we generate numbers, we need to make sure the target number of numbers from each
        // place is hit.
        for( $i = 0 ; $i < 6 ; $i++ ) {
            if ( '' != $_POST['given_number'][$i] ) {
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
        
        
        for( $i = 0 ; $i < 6 ; $i++ ) {
            if ( '' != $_POST['given_number'][$i] ) {
                $form_fields['user_numbers'][$i] = $_POST['given_number'][$i];
            } else {
                $form_fields['any_gaps'] = TRUE;
                // User number is blank. We want either a number in the set { 25, 50, 75, 100 }
                // or a number between 1 and 10. The user's choice of how many numbers to take from
                // the top line, and the number we already have from the top line, determine which
                // set we take the number from.
                if ( $form_fields['from']['top'] < $_POST['top_line'] ) {
                    $form_fields['user_numbers'][$i] = 25 * rand( 1, 4 );
                    $form_fields['from']['top']++;
                } else {
                    $form_fields['user_numbers'][$i] = rand( 1, 10 );
                    $form_fields['from']['anywhere']++;
                }
            }
        }
    }
    
    return $form_fields;
}

// This is the guts of the page. It takes the 6 given numbers and tries to
// find ways of combining them to reach the target number.
function process_form() {
    global $best_answer, $best_solution, $call_count;
    
    // Remember the current time so we can output how long we took to find a solution.
    $start_time = microtime( TRUE );  // TRUE means return it as a float.
    
    // Okay, the first solution will use each number once, and it won't do any fancy brackets.
    // It will just position the numbers in the 6! combinations, and they try every combination of +, -, * /
    // between them. Yes, that's 6! * 4^5 = ~750,000 combinations.
    // Generate 6 numbers; the nth number is the position of the nth given number.
    // Note: it feels like there should be a better way of doing this, but I can't think of it.
    // Yes, I know my variables should be $i0, $i1, $i2, etc.
    for( $i1 = 0 ; $i1 < 6 ; $i1++ ) {
        for( $i2 = 0 ; $i2 < 6 ; $i2++ ) {
            if ( $i1 != $i2 ) {
                for( $i3 = 0 ; $i3 < 6 ; $i3++ ) {
                    if ( $i1 != $i3 && $i2 != $i3 ) {
                        for( $i4 = 0 ; $i4 < 6 ; $i4++ ) {
                            if ( $i1 != $i4 && $i2 != $i4 && $i3 != $i4 ) {
                                for( $i5 = 0 ; $i5 < 6 ; $i5++ ) {
                                    if ( $i1 != $i5 && $i2 != $i5 && $i3 != $i5 && $i4 != $i5 ) {
                                        for( $i6 = 0 ; $i6 < 6 ; $i6++ ) {
                                            if ( $i1 != $i6 && $i2 != $i6 && $i3 != $i6 && $i4 != $i6 && $i5 != $i6 ) {
                                                // Call another method to continue...
                                                process_number_combo( array( $i1, $i2, $i3, $i4, $i5, $i6 ) );
                                                // If we finished, skip to the end.
                                                if ( isset( $best_answer ) && $best_answer == $_POST['target_number'] ) {
                                                    break 6;  // Break out of all the for loops.
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    $end_time = microtime( TRUE );  // TRUE means return it as a float.
    
    // Okay, let's see what we've ended up with.
    echo '<p >';
    if ( $best_answer == $_POST['target_number'] ) {
        echo 'Bingo! We hit the target!';
    } else {
        echo 'We missed the target by ' . abs( $best_answer - $_POST['target_number'] );
    }
    echo '</p>';
    echo '<p >';
    echo 'Solution: ' . $best_solution['solution'];
    echo '</p>';
    
    echo '<p >';
    $time_taken = round( $end_time - $start_time, 3 );
    echo 'Time taken: ' . $time_taken . ' seconds.';
    echo '<br />';
    echo 'Expressions evaluated: ' . $call_count;
    echo '</p>';
    
}

// Method that receives 6 positions. We then generate the 4^5 combinations of operators and apply
// them to see what number we get. If this answer is closer to the target than anything before,
// remember this answer and all the combinations.
function process_number_combo( $positions ) {
    global $best_answer;
// Just dump the positions.
//echo '<br />';
//for( $pidx = 0 ; $pidx < 6 ; $pidx++ ) {
//    echo $positions[$pidx] . ' ';
//}
    // We need 5 nested counters to go from 0 to 3 to give us the operators.
    // Yes, I know my variables should be $o0, $o1, etc.
    for( $o1 = 0 ; $o1 < 4 ; $o1++ ) {
        for( $o2 = 0 ; $o2 < 4 ; $o2++ ) {
            for( $o3 = 0 ; $o3 < 4 ; $o3++ ) {
                for( $o4 = 0 ; $o4 < 4 ; $o4++ ) {
                    for( $o5 = 0 ; $o5 < 4 ; $o5++ ) {
                        process_whole_expression( $positions, array( $o1, $o2, $o3, $o4, $o5 ) );
                        // If we finished, skip to the end.
                        if ( isset( $best_answer ) && $best_answer == $_POST['target_number'] ) {
                            break 5;  // Break out of all the for loops.
                        }
                    }
                }
            }
        }
    }
    
}

// This method receives the 6 positions and the 5 operators.
// It evaluates the expression - ie ( ( ( p1 o1 p2 ) o2 p3 ) o3 p4 )....
// If the answer is an integer and is closer than any previous expression, then remember everything.
function process_whole_expression( $positions, $operators ) {
    // TODO: We should do this in a class, then the "best so far information" could be private properties.
    //       We can also stop and skip to the end if we actually hit the solution.
    global $best_answer, $best_solution, $call_count;
    $call_count = ( isset( $call_count ) ? $call_count : 0 );
    
    // We have to handle a division by zero. If it happens, we ignore the answer.
    // We also ignore the answer if it's a non-integer during the process.
    $is_valid = TRUE;
    $expr = $_POST['given_number'][$positions[0]];
    $solution = $expr;
    $op_to_text = array( 0 => '+', 1 => '-', 2 => '*', 3 => '/' );
$debug = '<br />' . $expr;
    for( $oidx = 0 ; $oidx < 5 ; $oidx++ ) {
        $next_part = $_POST['given_number'][$positions[1 + $oidx]];
        $solution = '( ' . $solution . ' ' . $op_to_text[$operators[$oidx]] . ' ' . $next_part . ' )';
        switch( $operators[$oidx] ) {
            case 0:  // Plus
$debug .= ' + ';
                $expr = $expr + $next_part;
                break;
            case 1:  // Minus
$debug .= ' - ';
                $expr = $expr - $next_part;
                break;
            case 2:  // Times
$debug .= ' * ';
                $expr = $expr * $next_part;
                break;
            case 3:  // Divide
                if ( 0 == $next_part ) {
$debug .= ' !! ';
                    $is_valid = FALSE;
                } else {
$debug .= ' / ';
                    $expr = $expr / $next_part;
                }
                break;
        }
$debug .= $next_part;
        $is_valid = $is_valid && ( $expr == floor( $expr ) );
    }
//echo $debug;
    
//echo '<br />Next answer: ' . $expr;
    // We have the value of this expression. If it's an integer, see if it's closer than the previous best.
    if ( $is_valid ) {
        // If there isn't a previous best, then by definition this is the best so far.
        if ( ! isset( $best_answer ) ||
             ( abs( $_POST['target_number'] - $expr ) < abs( $_POST['target_number'] - $best_answer ) )
           ) {
            // Yes, we've found a better answer.
            $best_answer = $expr;
            $best_solution = array( 'positions' => $positions, 'operators' => $operators, 'solution' => $solution );
echo '<br />Best so far: ' . $best_answer . '; ' . $solution;
        }
    }

    $call_count++;
//if ( 100000 < $call_count ) {
//    die( 'Stopping' );
//}
}

?>