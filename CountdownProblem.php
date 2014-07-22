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
    ** This is the 2nd generation solver. It is much more efficient and actually much simpler than the first
    ** attempt at a solution. It can cope with any number of "given numbers" (within reason).
    */
    public function solve2( $debug_level = 0 ) {
        
        // Set the maximum page load time to 5 minutes.
        set_time_limit( 300 );
        
        // Store the debug level internally. If not valid, assume 0.
        // @TODO: if the debug lever is greater than, say 5, output to a file - you can imagine how much stuff there'll be!
        $this->debug_level = ( is_numeric( $debug_level ) && 0 <= $debug_level && 9 >= $debug_level ) ? $debug_level : 0;
        
        // We need the actual numbers so that we can evaluate the calculations
        // We need the expressions that generate those numbers, so that we can report back to the user.
        // In the top-level call, the expressions are just the numbers.
        $seedNumbers = array( 'values' => $this->given_numbers, 'expressions' => $this->given_numbers );
        
        // Remember what time we started.
        $start_time = microtime( TRUE );  // TRUE means return it as a float.
        
        // Start the whole process.
        $this->combineTwo( $this->target_number, $seedNumbers );
        
        // Get the finish time and save the difference.
        $end_time = microtime( TRUE );  // TRUE means return it as a float.
        $this->time_taken = round( $end_time - $start_time, 3 );
    }
    
    // Method that takes the target and an array of numbers. It takes every pair of numbers and for each pair, every
    // operator, and where it's worth doing it applies the operator to the two numbers. It then replaces the two
    // original numbers in the array and adds the result. It then calls itself again.
    // If the result is equal to the target, it stops the whole thing.
    private function combineTwo( $target, $remaining_numbers ) {
        if ( 1 < count( $remaining_numbers['values'] ) ) {
            for( $i = 0 ; $i < count( $remaining_numbers['values'] ) ; $i++ ) {
                for( $j = 0 ; $j < count( $remaining_numbers['values'] ) ; $j++ ) {
                    // Combine the ith and jth numbers with each operator, and call ourselves recursively.
                    if ( $i != $j ) {
                        $operators = array( '+', '-', '*', '/' );
                        $op1 = $remaining_numbers['values'][ $i ];
                        $op2 = $remaining_numbers['values'][ $j ];
                        foreach( $operators as $opidx => $operator ) {
                            // There are a few things that are not worth pursuing, so we test for them here.
                            // They are:
                            //   Dividing by zero
                            //   Trying "b + a" or "b * a" when we've previously done "a + b" or "a * b"
                            //   Trying "b <op> a" where a=b and we've done "a <op> b"
                            //   Multiplying or dividing by 1
                            $try_this = TRUE;
                            if ( '/' == $operator && 0 == $op2 ) {
                                if ( 4 < $this->debug_level ) {
                                    echo "<br />Not attempting to divide by zero.";
                                }
                                // Division by zero.
                                $try_this = FALSE;
                            } elseif ( $j < $i &&
                                       ( '+' == $operator || '*' == $operator ) ) {
                                if ( 4 < $this->debug_level ) {
                                    echo "<br />Already done " . $op1 . " " . $operator . " " . $op2 . " the other way round.";
                                }
                                // Doing a commutative operation the other way round.
                                $try_this = FALSE;
                            } elseif ( $op1 == $op2 &&
                                       $j < $i ) {
                                if ( 4 < $this->debug_level ) {
                                    echo "<br />Not doing " . $op1 . " " . $operator . " " . $op2 . " again.";
                                }
                                // Doing any operation the other way round when the operands are equal.
                                $try_this = FALSE;
                            } elseif ( ( ( 1 == $op1 || 1 == $op2 ) &&
                                         ( '*' == $operator ) ) ||
                                       ( 1 == $op2 && '/' == $operator ) ) {
                                if ( 4 < $this->debug_level ) {
                                    echo "<br />Not multiplying by 1.";
                                }
                                // Multiplying or dividing by 1
                                $try_this = FALSE;
                            }
                            if ( $try_this ) {
                                $is_valid = TRUE;
                                switch( $operator ) {
                                    case '+':
                                        $new_value = $op1 + $op2;
                                        break;
                                    case '-':
                                        $new_value = $op1 - $op2;
                                        // It's not valid if the answer is zero.
                                        $is_valid = ( 0 != $new_value );
                                        break;
                                    case '*':
                                        $new_value = $op1 * $op2;
                                        break;
                                    case '/':
                                        $new_value = $op1 / $op2;
                                        // It's only valid if the result is an integer.
                                        $is_valid = ( $new_value == floor( $new_value ) );
                                        break;
                                }
                                
                                // If anything is wrong, move on to the next operator/numbers.
                                if ( $is_valid ) {
                                    
                                    // Build the expression for the new value. It's just "( <expr1> <operator> <expr2> )".
                                    $new_expr = '( ' . $remaining_numbers['expressions'][ $i ] . ' ' . $operator . ' ' . $remaining_numbers['expressions'][ $j ] . ' )';
                                    
                                    // As this is valid, increment the call count.
                                    $this->call_count++;
                                    
                                    // See if this one value/expression is better than all previous ones.
                                    $this->isBetterSolutionFound( $new_value, $is_valid, $new_expr );
                                    
                                    // If we haven't hit the target, continue.
                                    if ( $this->areWeFinished() ) {
                                        break 3;
                                    }
                                    
                                    // Build the new set of numbers to pass in.
                                    $rem_nums = array( 'values' => array( $new_value ), 'expressions' => array( $new_expr ) );
                                    for( $ridx = 0 ; $ridx < count( $remaining_numbers['values'] ) ; $ridx++ ) {
                                        if ( $ridx != $i && $ridx != $j ) {
                                            $rem_nums['values'][] = $remaining_numbers['values'][$ridx];
                                            $rem_nums['expressions'][] = $remaining_numbers['expressions'][$ridx];
                                        }
                                    }
                                    $this->combineTwo( $target, $rem_nums );
                                }
                            }
                        }
                    }
                }
            }
            
        }
    }
    
    // Method to test whether the given expression is closer to the answer than the previous
    // one. Later we check if it's actually equal to it.
    private function isBetterSolutionFound( $this_answer, $is_valid, $this_solution ) {
        // We have the value of this expression. If it's an integer, see if it's closer than the previous best.
        if ( $is_valid ) {
            if ( 7 < $this->debug_level ) {
                echo '<br />Testing this expression: ' . $this_answer . ' = ' . $this_solution;
            }
            // If there isn't a previous best, then by definition this is the best so far.
            if ( NULL == $this->best_answer ||
                 ( abs( $this->target_number - $this_answer ) < abs( $this->target_number - $this->best_answer ) )
               ) {
                // Yes, we've found a better answer.
                $this->best_answer = $this_answer;
                $this->best_solution = array();
                $this->best_solution['solution'] = $this_solution;
                if ( 2 < $this->debug_level ) {
                    echo '<br />Best so far: ' . $this->best_answer . '; ' . $this_solution;
                }
            }
        }
    }
    
    // Method to return TRUE if the best answer so far is equal to the target.
    private function areWeFinished() {
        $success = ( NULL != $this->best_answer && $this->best_answer == $this->target_number );
        return $success;
    }
    
    // Methods to retrieve information about the outcome.
    // @TODO: These methods should check whether the solution has actually been run or not.
    
    // Method to return the value of the expression of the best answer.
    public function getBestAnswer() {
        // If we hit the target, and the debugging level is high enough, dump out all the information to screen.
        if ( $this->areWeFinished() && 3 < $this->debug_level ) {
            echo '<p ><pre >';
            print_r( $this->best_solution );
            echo '</pre></p>';
        }
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