/**
 * @package    assignsubmission_comparativejudgement
 * @copyright 2020 Andrew Hancox at Open Source Learning <andrewdchancox@googlemail.com>
 * @copyright 2020 Ian Jones at Loughborough University <I.Jones@lboro.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'],
    function ($) {
        return {
            init: function () {
                $('div#page-content').on('submit', 'div.comparisonbuttonrow form', function () {
                    $('input[name=comments_loser_2\\[format\\]]').val($('input[name=comments_winner_1\\[format\\]]').val());
                    $('textarea[name=comments_loser_2\\[text\\]]').val($('textarea[name=comments_winner_1\\[text\\]]').val());

                    $('input[name=comments_loser_1\\[format\\]]').val($('input[name=comments_winner_2\\[format\\]]').val());
                    $('textarea[name=comments_loser_1\\[text\\]]').val($('textarea[name=comments_winner_2\\[text\\]]').val());
                });

                $('div#page-content').on('click', 'button.comparisonbuttonleft', function () {
                    $('#id_buttonleft').trigger('click');
                });

                $('div#page-content').on('click', 'button.comparisonbuttonright', function () {
                    $('#id_buttonright').trigger('click');
                });
            }
        };
    });