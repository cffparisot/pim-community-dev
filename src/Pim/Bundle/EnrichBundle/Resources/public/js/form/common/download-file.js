'use strict';
/**
 * Download file extension
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @author    Alban Alnot <alban.alnot@consertotech.pro>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
define(
    [
        'underscore',
        'oro/translator',
        'pim/form',
        'text!pim/template/form/download-file',
        'routing',
        'pim/user-context',
        'pim/common/property'
    ],
    function (_,
              __,
              BaseForm,
              template,
              Routing,
              UserContext,
              propertyAccessor) {

        return BaseForm.extend({
            template: _.template(template),

            /**
             * {@inheritdoc}
             */
            initialize: function (meta) {
                this.config = meta.config;

                BaseForm.prototype.initialize.apply(this, arguments);
            },

            /**
             * {@inheritdoc}
             */
            configure: function () {
                if (this.config.updateOnEvent) {
                    this.listenTo(this.getRoot(), this.config.updateOnEvent, function (newData) {
                        this.setData(newData);
                        this.render();
                    });
                }

                return BaseForm.prototype.configure.apply(this, arguments);
            },

            /**
             * {@inheritdoc}
             */
            render: function () {
                if (!this.isVisible()) {
                    return this;
                }
                this.$el.html(this.template({
                    btnLabel: __(this.config.label),
                    btnIcon: this.config.iconName
                }));
                this.$el.attr('href', this.getUrl());

                return this;
            },

            /**
             * Get the url with parameters
             * @returns {*}
             */
            getUrl: function () {
                if (this.config.url) {
                    var parameters = {};
                    if (this.config.urlParams) {
                        var formData = this.getFormData();
                        this.config.urlParams.forEach(function (urlParam) {
                            parameters[urlParam.property] =
                                propertyAccessor.accessProperty(formData, urlParam.path);
                        });
                    }

                    return Routing.generate(
                        this.config.url,
                        parameters);
                } else {
                    return '';
                }
            },

            /**
             * Returns true if the extension should be visible
             * @returns {*}
             */
            isVisible: function () {
                return this.config.isVisiblePath ?
                    propertyAccessor.accessProperty(
                        this.getFormData(),
                        this.config.isVisiblePath
                    )
                    : true;
            }
        });
    }
);
