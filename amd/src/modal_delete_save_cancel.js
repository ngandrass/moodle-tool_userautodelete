// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Contain the logic for the delete/save/cancel modal.
 *
 * @module     tool_userautodelete/modal_delete_save_cancel
 * @copyright  2016 Ryan Wyllie <ryan@moodle.com>
 * @copyright  2026 Niels Gandraß <niels@gandrass.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Modal from 'core/modal';
import Notification from 'core/notification';

/**
 * A Delete/Save/Cancel Modal.
 *
 * @class
 * @extends module:core/modal
 */
export default class ModalDeleteSaveCancel extends Modal {
    static TYPE = 'DELETE_SAVE_CANCEL';
    static TEMPLATE = 'tool_userautodelete/modal/delete_save_cancel';

    constructor(root) {
        super(root);

        if (!this.getFooter().find(this.getActionSelector('delete')).length) {
            Notification.exception({message: 'No delete button found'});
        }

        if (!this.getFooter().find(this.getActionSelector('save')).length) {
            Notification.exception({message: 'No save button found'});
        }

        if (!this.getFooter().find(this.getActionSelector('cancel')).length) {
            Notification.exception({message: 'No cancel button found'});
        }
    }

    /**
     * Register all event listeners.
     */
    registerEventListeners() {
        // Call the parent registration.
        super.registerEventListeners();

        // Register to close on save/cancel.
        this.registerCloseOnDelete();
        this.registerCloseOnSave();
        this.registerCloseOnCancel();
    }

    /**
     * Override parent implementation to prevent changing the footer content.
     */
    setFooter() {
        Notification.exception({message: 'Can not change the footer of a delete save cancel modal'});
        return;
    }

    /**
     * Set the title of the save button.
     *
     * @param {String|Promise} value The button text, or a Promise which will resolve it
     * @returns{Promise}
     */
    setSaveButtonText(value) {
        return this.setButtonText('save', value);
    }
}

ModalDeleteSaveCancel.registerModalType();
