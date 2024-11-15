/**
 * @copyright 2020 Andrew Hancox at Open Source Learning <andrewdchancox@googlemail.com>
 * @copyright 2020 Ian Jones at Loughborough University <I.Jones@lboro.ac.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification'],
    function($, ajax, notification) {
        return {
            init: function(entitytype) {
                $(document).on('change', 'input.excludeentity', function () {
                    var chkbox = $(this);
                    var entityid = chkbox.data('entityid');
                    var state = chkbox.is(':checked');
                    chkbox.attr('disabled', 'disabled');

                    ajax.call([{
                        methodname: 'assignsubmission_comparativejudgement_toggle_exclusion',
                        args: {entityid: entityid, state: state, entitytype: entitytype}
                    }])[0].then(function() {
                        chkbox.removeAttr('disabled', 'disabled');
                        return true;
                    }).fail(notification.exception);
                });
            }
        };
    });