/*
 * Copyright (C) Ascensio System SIA, 2009-2026
 *
 * This program is a free software product. You can redistribute it and/or
 * modify it under the terms of the GNU Affero General Public License (AGPL)
 * version 3 as published by the Free Software Foundation, together with the
 * additional terms provided in the LICENSE file.
 *
 * This program is distributed WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. For
 * details, see the GNU AGPL at: https://www.gnu.org/licenses/agpl-3.0.html
 *
 * You can contact Ascensio System SIA by email at info@onlyoffice.com
 * or by postal mail at 20A-6 Ernesta Birznieka-Upisha Street, Riga,
 * LV-1050, Latvia, European Union.
 *
 * The interactive user interfaces in modified versions of the Program
 * are required to display Appropriate Legal Notices in accordance with
 * Section 5 of the GNU AGPL version 3.
 *
 * No trademark rights are granted under this License.
 *
 * All non-code elements of the Product, including illustrations,
 * icon sets, and technical writing content, are licensed under the
 * Creative Commons Attribution-ShareAlike 4.0 International License:
 * https://creativecommons.org/licenses/by-sa/4.0/legalcode
 *
 * This license applies only to such non-code elements and does not
 * modify or replace the licensing terms applicable to the Program's
 * source code, which remains licensed under the GNU Affero General
 * Public License v3.
 *
 * SPDX-License-Identifier: AGPL-3.0-only
 */
/**
 *    Chat.js
 *
 *    Controller
 *
 *    Created on 27 February 2014
 *
 */

define([
    'core',
    'common/main/lib/collection/Users',
    'common/main/lib/collection/ChatMessages',
    'common/main/lib/view/Chat'
], function () {
    'use strict';

    var P3_AI_USER_ID = 'p3-lightning-ai';

    Common.Controllers.Chat = Backbone.Controller.extend(_.extend({
        models: [],
        collections: [
            'Common.Collections.Users',
            'Common.Collections.ChatMessages'
        ],
        views: [
            'Common.Views.Chat'
        ],

        initialize: function() {
            this.addListeners({
                'Common.Views.Chat': {
                    'message:add': _.bind(this.onSendMessage, this)
                }
            });

            var me = this;
            Common.NotificationCenter.on('layout:changed', function(area){
                Common.Utils.asyncCall(function(e) {
                    if ( (e == 'toolbar' || e == 'status') && me.panelChat.$el.is(':visible') ) {
                        me.panelChat.updateLayout(true);
                        me.panelChat.setupAutoSizingTextBox();
                    }
                }, this, area);
            });
        },

        events: {
        },

        _aiSettings: function() {
            return (typeof window !== 'undefined') ? window.P3_LIGHTNING_AI : null;
        },

        _ensureAiUser: function() {
            var settings = this._aiSettings();
            if (!settings) return;

            var usersStore = this.getApplication().getCollection('Common.Collections.Users');
            if (!usersStore || usersStore.findUser(P3_AI_USER_ID)) return;

            usersStore.add(new Common.Models.User({
                id          : P3_AI_USER_ID,
                idOriginal  : P3_AI_USER_ID,
                username    : 'Lightning AI',
                parsedName  : 'Lightning AI',
                initials    : 'LA',
                online      : true,
                color       : settings.color || '#00A868',
                view        : true,
                hidden      : false
            }));
        },

        _applyAiEdit: function(edit) {
            try {
                var settings = this._aiSettings() || {};
                if (settings.canEdit === false) return false;
                var editor = window.editor;
                if (!editor || typeof editor.pluginMethod_SearchAndReplace !== 'function') return false;

                var search = String(edit.searchString || '');
                if (!search) return false;

                // The server matched this phrase against the saved document.
                // Do not pre-search here: OnlyOffice's search cursor would
                // then be past the match when SearchAndReplace runs.
                editor.pluginMethod_SearchAndReplace({
                    searchString : search,
                    replaceString: String(edit.replaceString || ''),
                    matchCase    : edit.matchCase !== false
                });
                return true;
            } catch (e) {
                if (window.console && console.error) console.error('P-ARCHIVE AI edit failed', e);
                return false;
            }
        },

        _askAi: function(text) {
            var settings = this._aiSettings();
            if (!settings || !settings.endpoint || !text) return;

            var me = this;
            var body = new URLSearchParams();
            body.append('question', text);
            var separator = settings.endpoint.indexOf('?') >= 0 ? '&' : '?';
            var url = settings.endpoint + separator + 'token=' + encodeURIComponent(settings.token || '');

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                body: body.toString()
            })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (!data || !data.answer) return;

                    var answer = data.answer;
                    if (data.edit) {
                        if (!me._applyAiEdit(data.edit)) {
                            answer += '<p class="mb-2">I could not apply that change in the open editor. Check that the exact text is still in the document and that editing is enabled.</p>';
                        }
                    }

                    var msgStore = me.getApplication().getCollection('Common.Collections.ChatMessages');
                    if (!msgStore) return;

                    msgStore.add(new Common.Models.ChatMessage({
                        userid      : P3_AI_USER_ID,
                        username    : 'Lightning AI',
                        parsedName  : 'Lightning AI',
                        message     : answer
                    }));
                })
                .catch(function() {});
        },

        onLaunch: function() {
            this.panelChat = this.createView('Common.Views.Chat', {
                storeUsers: this.getApplication().getCollection('Common.Collections.Users'),
                storeMessages: this.getApplication().getCollection('Common.Collections.ChatMessages')
            });
            this.panelChat.on('render:after', _.bind(this.onAfterRender, this));
        },

        onAfterRender: function(panel) {
            var viewport = this.getApplication().getController('Viewport').getView('Viewport');
            viewport.hlayout.on('layout:resizedrag',  _.bind(function () {
                panel && panel.updateScrolls();
            }, this));
        },

        setMode: function(mode) {
            this.mode = mode;
            this.currentUserId = mode.user.id;

            if (this.api) {
                var canRegister = typeof this.api.asc_registerCallback === 'function';

                if (canRegister && this.mode.canCoAuthoring && this.mode.canChat)
                    this.api.asc_registerCallback('asc_onCoAuthoringChatReceiveMessage', _.bind(this.onReceiveMessage, this));

                if ( !this.mode.isEditDiagram && !this.mode.isEditMailMerge && !this.mode.isEditOle ) {
                    Common.NotificationCenter.on('mentions:setusers', _.bind(this.avatarsUpdate, this));
                    if (canRegister) {
                        this.api.asc_registerCallback('asc_onAuthParticipantsChanged', _.bind(this.onUsersChanged, this));
                        this.api.asc_registerCallback('asc_onConnectionStateChanged', _.bind(this.onUserConnection, this));
                    }
                    if (typeof this.api.asc_coAuthoringGetUsers === 'function')
                        this.api.asc_coAuthoringGetUsers();
                }

                if (this.mode.canCoAuthoring && this.mode.canChat && typeof this.api.asc_coAuthoringChatGetMessages === 'function')
                    this.api.asc_coAuthoringChatGetMessages();
            }

            this._ensureAiUser();
            return this;
        },

        setApi: function(api) {
            this.api = api;

            return this;
        },

        onUsersChanged: function(users, currentUserId){
            if (!this.mode.canLicense || !this.mode.canCoAuthoring) {
                var len = 0;
                for (name in users) {
                    if (undefined !== name) len++;
                }
                if (len>1 && this._isCoAuthoringStopped==undefined) {
                    this._isCoAuthoringStopped = true;
                    this.api.asc_coAuthoringDisconnect();
                    Common.NotificationCenter.trigger('api:disconnect');

                    return;
                }
            }

            var usersStore = this.getApplication().getCollection('Common.Collections.Users');

            if (usersStore) {
                var arrUsers = [], name, user, arrIds = [];

                for (name in users) {
                    if (undefined !== name) {
                        user = users[name];
                        if (user) {
                            var avatar = Common.UI.ExternalUsers.getImage(user.asc_getIdOriginal());
                            var usermodel = new Common.Models.User({
                                id          : user.asc_getId(),
                                idOriginal  : user.asc_getIdOriginal(),
                                username    : user.asc_getUserName(),
                                parsedName  : AscCommon.UserInfoParser.getParsedName(user.asc_getUserName()),
                                initials    : Common.Utils.getUserInitials(AscCommon.UserInfoParser.getParsedName(user.asc_getUserName())),
                                online      : true,
                                color       : user.asc_getColor(),
                                avatar      : avatar,
                                view        : user.asc_getView(),
                                hidden      : !(user.asc_getIdOriginal()===this.currentUserId || AscCommon.UserInfoParser.isUserVisible(user.asc_getUserName()))
                            });
                            arrUsers[(user.asc_getId() == currentUserId ) ? 'unshift' : 'push'](usermodel);
                            (avatar===undefined) && arrIds.push(user.asc_getIdOriginal());
                        }
                    }
                }
                usersStore.reset(arrUsers);
                this._ensureAiUser();
                arrIds.length && Common.UI.ExternalUsers.get('info', arrIds);
            }
        },

        onUserConnection: function(change){
            var usersStore = this.getApplication().getCollection('Common.Collections.Users');

            if (usersStore){
                var user = usersStore.findUser(change.asc_getId());
                var avatar = Common.UI.ExternalUsers.getImage(change.asc_getIdOriginal());
                if (!user) {
                    usersStore.add(new Common.Models.User({
                        id          : change.asc_getId(),
                        idOriginal  : change.asc_getIdOriginal(),
                        username    : change.asc_getUserName(),
                        parsedName  : AscCommon.UserInfoParser.getParsedName(change.asc_getUserName()),
                        initials    : Common.Utils.getUserInitials(AscCommon.UserInfoParser.getParsedName(change.asc_getUserName())),
                        online      : change.asc_getState(),
                        color       : change.asc_getColor(),
                        avatar      : avatar,
                        view        : change.asc_getView(),
                        hidden      : !(change.asc_getIdOriginal()===this.currentUserId || AscCommon.UserInfoParser.isUserVisible(change.asc_getUserName()))
                    }));
                } else {
                    user.set({online: change.asc_getState()});
                    user.set({username: change.asc_getUserName()});
                    user.set({parsedName: AscCommon.UserInfoParser.getParsedName(change.asc_getUserName())});
                    user.set({initials: Common.Utils.getUserInitials(change.asc_getUserName())});
                    user.set({avatar: avatar});
                }
                (avatar===undefined) && Common.UI.ExternalUsers.get('info', [change.asc_getIdOriginal()]);
                this._ensureAiUser();
            }
        },

        avatarsUpdate: function(type, users) {
            if (type!=='info') return;

            var usersStore = this.getApplication().getCollection('Common.Collections.Users'),
                msgStore = this.getApplication().getCollection('Common.Collections.ChatMessages'),
                needrender = false;
            if (users && users.length>0 ){
                _.each(users, function(item) {
                    usersStore && usersStore.findOriginalUsers(item.id).forEach(function(user){
                        user.set({avatar: item.image});
                    });
                    msgStore && msgStore.where({userid: item.id}).forEach(function(msg){
                        if (item.image!==undefined && item.image !== msg.get('avatar')) {
                            needrender = true;
                            msg.set('avatar', item.image, {silent: true});
                        }
                    });
                });
            }
            needrender && this.panelChat && this.panelChat.renderMessages();
        },

        onReceiveMessage: function(messages, clear){
            var msgStore = this.getApplication().getCollection('Common.Collections.ChatMessages');

            if ( msgStore ) {
                var array = [];
                messages.forEach(function(msg) {
                    array.push(new Common.Models.ChatMessage({
                        userid      : msg.useridoriginal,
                        message     : msg.message,
                        username    : msg.username,
                        parsedName  : AscCommon.UserInfoParser.getParsedName(msg.username),
                    }));
                });

                msgStore[(msgStore.size() < 1 || clear) ? 'reset' : 'add'](array);
            }
        },

        onSendMessage: function(panel, text){
            if (text.length > 0){
                var splitString = function(string, chunkSize) {
                    var chunks = [];

                    while (string) {
                        if (string.length < chunkSize) {
                            chunks.push(string);
                            break;
                        } else {
                            chunks.push(string.substr(0, chunkSize));
                            string = string.substr(chunkSize);
                        }
                    }

                    return chunks;
                };

                var me = this;
                this._askAi(text);
                if (this.api && typeof this.api.asc_coAuthoringChatSendMessage === 'function') {
                    splitString(text, 2048).forEach(function(message) {
                        try {
                            me.api.asc_coAuthoringChatSendMessage(message);
                        } catch (e) {}
                    });
                }
            }
        },

        notcriticalErrorTitle: 'Warning'
    }, Common.Controllers.Chat || {}));
});