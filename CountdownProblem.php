<?php

/*
** This class solves the Countdown Numbers problem.
** When instantiated, the target and the 6 numbers are passed in.
** Then you call the method to solve it.
** Then you use various methods to get the answer out.
*/

class CountdownProblem {
    
    private $target_number;
    private $debug_level;
    
    private $best_answer = NULL;
    private $best_solution = NULL;
    private $call_count = 0;
    private $time_taken = NULL;
    
    // Cnstructor function.
    // Target number is an integer between 100 and 999 inclusive.
    // given_numbers is an array of 6 integers.
    function __construct( $target_number, $given_numbers ) {
        // @TODO: We should validate these numbers.
        $this->target_number = $target_number;
        $this->given_numbers = $given_numbers;
    }
    
    /*
    ** This method attempts to solve the problem. It stores the outcome (TRUE/FALSE)
    ** and all the information about the solution.
    ** The debug level determines how much internal working is output (0 = none, 9 = lots).
    */
    public function solve( $debug_level = 0 ) {
        
        // Store the debug level internally. If not valid, assume 0.
        // @TODO: if the debug lever is greater than, say 5, output to a file - you can imagine how much stuff there'll be!
        $this->debug_level = ( is_numeric( $debug_level ) && 0 <= $debug_level && 9 >= $debug_level ) ? $debug_level : 0;
        
        // Remember what time we started.
        $start_time = microtime( TRUE );  // TRUE means return it as a float.
        
        // Okay, the first attempt at a solution will use each number once, and it won't do any fancy brackets.
        // It will just position the numbers in the 6! combinations, and they try every combination of +, -, * /
        // between them. Yes, that's 6! * 4^5 = ~750,000 combinations.
        // Generate 6 numbers; the nth number is the position of the nth given number.
        // Note: it feels like there should be a better way of doing this, but I can't think of it.
        for( $i0 = 0 ; $i0 < 6 ; $i0++ ) {
            for( $i1 = 0 ; $i1 < 6 ; $i1++ ) {
                if ( $i0 != $i1 ) {
                    for( $i2 = 0 ; $i2 < 6 ; $i2++ ) {
                        if ( $i0 != $i2 && $i1 != $i2 ) {
                            for( $i3 = 0 ; $i3 < 6 ; $i3++ ) {
                                if ( $i0 != $i3 && $i1 != $i3 && $i2 != $i3 ) {
                                    for( $i4 = 0 ; $i4 < 6 ; $i4++ ) {
                                        if ( $i0 != $i4 && $i1 != $i4 && $i2 != $i4 && $i3 != $i4 ) {
                                            for( $i5 = 0 ; $i5 < 6 ; $i5++ ) {
                                                if ( $i0 != $i5 && $i1 != $i5 && $i2 != $i5 && $i3 != $i5 && $i4 != $i5 ) {
                                                    // Call another method to continue...
                                                    $this->process_number_combo( array( $i0, $i1, $i2, $i3, $i4, $i5 ) );
                                                    // If we finished, skip to the end.
                                                    if ( NULL != $this->best_answer && $this->best_answer == $this->target_number ) {
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
        $this->time_taken = round( $end_time - $start_time, 3 );
    }
    
    // Method that receives 6 positions. We then generate the 4^5 combinations of operators and apply
    // them to see what number we get. If this answer is closer to the target than anything before,
    // remember this answer and all the combinations.
    private function process_number_combo( $positions ) {
        // If the debug level is fairly high, dump the arrangement of the positions.
        if ( 7 < $this->debug_level ) {
            $pos_dump = '';
            for( $pidx = 0 ; $pidx < 6 ; $pidx++ ) {
                $pos_dump .= $positions[$pidx] . ' ';
            }
            $pos_dump = rtrim( $pos_dump );
            echo '<br />' . $pos_dump;
        }
        
        // We need 5 nested counters to go from 0 to 3 to give us the operators.
        for( $o0 = 0 ; $o0 < 4 ; $o0++ ) {
            for( $o1 = 0 ; $o1 < 4 ; $o1++ ) {
                for( $o2 = 0 ; $o2 < 4 ; $o2++ ) {
                    for( $o3 = 0 ; $o3 < 4 ; $o3++ ) {
                        for( $o4 = 0 ; $o4 < 4 ; $o4++ ) {
                            $this->process_whole_expression( $positions, array( $o0, $o1, $o2, $o3, $o4 ) );
                            // If we finished, skip to the end.
                            // @TODO: Move this test into a separate method so we can call it everywhere.
                            if ( NULL != $this->best_answer && $this->best_answer == $this->target_number ) {
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
    private function process_whole_expression( $positions, $operators ) {
        $op_to_text = array( 0 => '+', 1 => '-', 2 => '*', 3 => '/' );
        
        // If the debug level is fairly high, dump the combination of operators.
        if ( 7 < $this->debug_level ) {
            $op_dump = '';
            for( $oidx = 0 ; $oidx < 5 ; $oidx++ ) {
                $op_dump .= $op_to_text[$operators[$oidx]] . ' ';
            }
            $op_dump = rtrim( $op_dump );
            echo '<br />' . $op_dump;
        }
        
        // We have to handle a division by zero. If it happens, we ignore the answer.
        // We also ignore the answer if it's a non-integer during the process.
        $is_valid = TRUE;
        $expr = $this->given_numbers[$positions[0]];
        $solution = $expr;
        $debug3 = '<br />This expression: ' . $expr;
        for( $oidx = 0 ; $oidx < 5 ; $oidx++ ) {
            $next_part = $this->given_numbers[$positions[1 + $oidx]];
            $solution = '( ' . $solution . ' ' . $op_to_text[$operators[$oidx]] . ' ' . $next_part . ' )';
            switch( $operators[$oidx] ) {
                case 0:  // Plus
                    $debug3 .= ' + ';
                    $expr = $expr + $next_part;
                    break;
                case 1:  // Minus
                    $debug3 .= ' - ';
                    $expr = $expr - $next_part;
                    break;
                case 2:  // Times
                    $debug3 .= ' * ';
                    $expr = $expr * $next_part;
                    break;
                case 3:  // Divide
                    if ( 0 == $next_part ) {
                        $debug3 .= ' !! ';
                        $is_valid = FALSE;
                    } else {
                        $debug3 .= ' / ';
                        $expr = $expr / $next_part;
                    }
                    break;
            }
            $debug3 .= $next_part;
            $is_valid = $is_valid && ( $expr == floor( $expr ) );
        }
        if ( 4 < $this->debug_level ) {
            echo $debug3;
            echo ' gives: ' . $expr;
        }
        
        // We have the value of this expression. If it's an integer, see if it's closer than the previous best.
        if ( $is_valid ) {
            // If there isn't a previous best, then by definition this is the best so far.
            if ( NULL == $this->best_answer ||
                 ( abs( $this->target_number - $expr ) < abs( $this->target_number - $this->best_answer ) )
               ) {
                // Yes, we've found a better answer.
                $this->best_answer = $expr;
                $this->best_solution = array( 'positions' => $positions, 'operators' => $operators, 'solution' => $solution );
echo '<br />Best so far: ' . $this->best_answer . '; ' . $solution;
            }
        }

        $this->call_count++;
        
    }
    
    // Methods to retrieve information about the outcome.
    // @TODO: These methods should check whether the solution has actually been run or not.
    
    // Method to return the value of the expression of the best answer.
    public function getBestAnswer() {
        return $this->best_answer;
    }
    
    // Method to return the amount by which we missed the target.
    // It's the ABS of the difference between the target and the best answer.
    public function getMissedTargetByAmount() {
        return abs( $this->best_answer - $this->target_number );
    }
    
    // Method to return the expression of the best solution as a string.
    public function getBestSolutionAsString() {
        return $this->best_solution['solution'];
    }
    
    // Method to return the time taken in seconds.
    // We return the value to 3 places of decimals.
    public function getTimeTakenSeconds() {
        return $this->time_taken;
    }
    
    // Method to return the number of expressions we tested.
    public function getCallCount() {
        return $this->call_count;
    }
    
}






?>