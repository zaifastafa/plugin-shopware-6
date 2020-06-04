(this.webpackJsonp=this.webpackJsonp||[]).push([["fin-search"],{"3ALG":function(e,n,t){var i=t("Vl01");"string"==typeof i&&(i=[[e.i,i,""]]),i.locals&&(e.exports=i.locals);(0,t("SZ7m").default)("4ac58746",i,!0,{})},"7+Lj":function(e){e.exports=JSON.parse('{"findologic":{"header":"FINDOLOGIC","general":{"mainMenuDescription":"FINDOLOGIC Plugin für das Shopware 6 E-Commerce System"},"fieldRequired":"Shopkey ist erforderlich.","invalidShopkey":"Ungültiger Shopkey.","notRegisteredShopkey":"Der eingegebene Shopkey ist keinem bekannten Service zugeordnet.","settingForm":{"configSaved":"Konfiguration gespeichert.","testButton":"Testmodus","config":{"title":"Konfiguration","shopkey":{"label":"Shopkey","tooltipText":"FINDOLOGIC shopkey"},"active":{"label":"Aktiv","tooltipText":"Aktiviert die FINDOLOGIC Suche."},"activeOnCategoryPages":{"label":"Aktiv auf Kategorieseiten","tooltipText":"Aktiviert die FINDOLOGIC Suche für Kategorieseiten."},"searchResultContainer":{"label":"CSS-Klasse für Suchresultat","placeholder":"fl-result","tooltipText":"Diese Option ist nur bei Direct Integration wirksam, wenn kein Wert gegeben ist wird fl-result verwendet"},"navigationResultContainer":{"label":"CSS-Klasse für Navigation","placeholder":"fl-navigation-result","tooltipText":"Diese Option ist nur bei Direct Integration wirksam, wenn kein Wert gegeben ist wird fl-navigation-result verwendet."},"integrationType":{"label":"Integration (schreibgeschützt)","tooltipText":"Die aktuell verwendete Integrationsart. Entweder Direct Integration oder API."}},"titleSuccess":"Erfolgreich","titleError":"Fehler"},"error":{"title":"Fehler"}}}')},HerF:function(e,n,t){"use strict";t.r(n);var i=t("MCfI"),o=t.n(i);const{Component:a}=Shopware;a.override("sw-plugin-list",{template:o.a});var l=t("eRAB"),s=t.n(l);t("3ALG");const{Component:c,Mixin:r,Application:d,Utils:h}=Shopware;c.register("findologic-page",{template:s.a,mixins:[r.getByName("notification")],data:()=>({isLoading:!1,isSaveSuccessful:!1,isStagingShop:!1,isValidShopkey:!1,isRegisteredShopkey:null,isActive:!1,shopkeyAvailable:!1,config:null,shopkeyErrorState:null,httpClient:d.getContainer("init").httpClient}),metaInfo(){return{title:this.$createTitle()}},watch:{config:{handler(){const e=this.$refs.configComponent.allConfigs.null;if(null===this.$refs.configComponent.selectedSalesChannelId?(this.shopkeyAvailable=!!this.config["FinSearch.config.shopkey"],this.isActive=!!this.config["FinSearch.config.active"]):(this.shopkeyAvailable=!!this.config["FinSearch.config.shopkey"]||!!e["FinSearch.config.shopkey"],this.isActive=!!this.config["FinSearch.config.active"]||!!e["FinSearch.config.active"]),this.shopkeyAvailable){const e=this._getShopkey();if(this._isShopkeyValid(e)){this.shopkeyErrorState=null;const n=h.format.md5(e).toUpperCase();this._isStagingRequest(n)}}this._setErrorStates()},deep:!0}},methods:{_isShopkeyValid(e){return this.isValidShopkey=!1!==/^[A-F0-9]{32}$/.test(e),this.isValidShopkey},_getShopkey(){const e=this.$refs.configComponent.allConfigs.null;let n=this.config["FinSearch.config.shopkey"];return!!n||(n=e["FinSearch.config.shopkey"]),n},_isStagingRequest(e){this.httpClient.get(`https://cdn.findologic.com/static/${e}/config.json`).then(e=>{e.data.isStagingShop&&(this.isStagingShop=!0)}).catch(()=>{this.isStagingShop=!1})},onSave(){this.shopkeyAvailable&&this.isValidShopkey?this._validateShopkeyFromService().then(e=>{this.isRegisteredShopkey=e}).then(()=>{this.isRegisteredShopkey?this._save():this._setErrorStates(!0)}):this._setErrorStates(!0)},_save(){this.$refs.configComponent.save().then(e=>{this.shopkeyErrorState=null,this.isLoading=!1,this.isSaveSuccessful=!0,this.createNotificationSuccess({title:this.$tc("findologic.settingForm.titleSuccess"),message:this.$tc("findologic.settingForm.configSaved")}),e&&(this.config=e)}).catch(()=>{this.isSaveSuccessful=!1,this.isLoading=!1})},_setErrorStates(e=!1){this.isLoading=!1,this.shopkeyAvailable?this.isValidShopkey?!1===this.isRegisteredShopkey?this.shopkeyErrorState={code:1,detail:this.$tc("findologic.notRegisteredShopkey")}:this.shopkeyErrorState=null:this.shopkeyErrorState={code:1,detail:this.$tc("findologic.invalidShopkey")}:this.shopkeyErrorState={code:1,detail:this.$tc("findologic.fieldRequired")},e&&this._showNotification()},_showNotification(){this.shopkeyAvailable?this.isValidShopkey?!1===this.isRegisteredShopkey&&this.createNotificationError({title:this.$tc("findologic.settingForm.titleError"),message:this.$tc("findologic.notRegisteredShopkey")}):this.createNotificationError({title:this.$tc("findologic.settingForm.titleError"),message:this.$tc("findologic.invalidShopkey")}):this.createNotificationError({title:this.$tc("findologic.settingForm.titleError"),message:this.$tc("findologic.fieldRequired")})},_validateShopkeyFromService(){return this.isLoading=!0,this.httpClient.get(`https://account.findologic.com/api/v1/shopkey/validate/${this._getShopkey()}`).then(e=>String(e.status).startsWith("2")).catch(()=>!1)}}});var g=t("T6xp"),p=t.n(g);const{Component:f,Mixin:u}=Shopware,{Criteria:S}=Shopware.Data;f.register("findologic-config",{template:p.a,name:"FindologicConfig",inject:["repositoryFactory"],mixins:[u.getByName("notification")],props:{actualConfigData:{type:Object,required:!0},allConfigs:{type:Object,required:!0},shopkeyErrorState:{required:!0},selectedSalesChannelId:{type:String,required:!1,default:null},isStagingShop:{type:Boolean,required:!0,default:!1},isValidShopkey:{type:Boolean,required:!0,default:!1},isActive:{type:Boolean,required:!0,default:!1},shopkeyAvailable:{type:Boolean,required:!0,default:!1}},data:()=>({isLoading:!1}),methods:{checkTextFieldInheritance:e=>"string"!=typeof e||e.length<=0,checkBoolFieldInheritance:e=>"boolean"!=typeof e,openSalesChannelUrl(){if(null!==this.selectedSalesChannelId){const e=new S;e.addFilter(S.equals("id",this.selectedSalesChannelId)),e.setLimit(1),e.addAssociation("domains"),this.salesChannelRepository.search(e,Shopware.Context.api).then(e=>{const n=e.first().domains.first();this._openStagingUrl(n)})}else this._openDefaultUrl()},_openDefaultUrl(){const e=`${window.location.origin}?findologic=on`;window.open(e,"_blank")},_openStagingUrl(e){if(e){const n=`${e.url}?findologic=on`;window.open(n,"_blank")}else this._openDefaultUrl()}},computed:{showTestButton(){return this.isActive&&this.shopkeyAvailable&&this.isValidShopkey&&this.isStagingShop},salesChannelRepository(){return this.repositoryFactory.create("sales_channel")}}});var k=t("Pf90"),m=t("7+Lj");const{Module:v}=Shopware;v.register("findologic-module",{type:"plugin",name:"FinSearch",title:"findologic.header",description:"findologic.general.mainMenuDescription",color:"#f7ff0f",snippets:{"de-DE":m,"en-GB":k},routes:{index:{component:"findologic-page",path:"index",meta:{parentPath:"sw.settings.index"}}}})},MCfI:function(e,n){e.exports="{% block sw_plugin_list_grid_columns_actions_settings %}\n    <template v-if=\"item.composerName === 'findologic/plugin-shopware-6'\">\n        <sw-context-menu-item :routerLink=\"{ name: 'findologic.module.index' }\">\n            {{ $tc('sw-plugin.list.config') }}\n        </sw-context-menu-item>\n    </template>\n\n    <template v-else>\n        {% parent %}\n    </template>\n{% endblock %}\n"},Pf90:function(e){e.exports=JSON.parse('{"findologic":{"header":"FINDOLOGIC","general":{"mainMenuDescription":"FINDOLOGIC plugin for Shopware 6 e-commerce system"},"fieldRequired":"Shopkey is required.","invalidShopkey":"Invalid Shopkey.","notRegisteredShopkey":"The given shopkey is not associated to any known service.","settingForm":{"configSaved":"Configuration saved.","testButton":"Test mode","config":{"title":"Configuration","shopkey":{"label":"Shopkey","tooltipText":"FINDOLOGIC shopkey"},"active":{"label":"Active","tooltipText":"Activate the FINDOLOGIC search provider."},"activeOnCategoryPages":{"label":"Active on category pages","tooltipText":"Activate the FINDOLOGIC search provider for category pages."},"searchResultContainer":{"label":"CSS class for search result","placeholder":"fl-result","tooltipText":"This option has an effect for Direct Integration only, when empty fl-result is used."},"navigationResultContainer":{"label":"CSS class for navigation","placeholder":"fl-navigation-result","tooltipText":"This option has an effect for Direct Integration only, when empty fl-navigation-result is used."},"integrationType":{"label":"Integration (read-only)","tooltipText":"Currently used integration type. Either one of Direct Integration or API."}},"titleSuccess":"Success","titleError":"Error"},"error":{"title":"Error"}}}')},T6xp:function(e,n){e.exports='{% block findologic_credentials %}\n<sw-card class="sw-card--grid" :title="$tc(\'findologic.settingForm.config.title\')"\n>\n    {% block findologic_credentials_card_container %}\n    <sw-container>\n        {% block findologic_credentials_settings %}\n        <div class="findologic-settings-credentials-fields" v-if="actualConfigData">\n            {% block findologic_credentials_settings_shopkey %}\n                <sw-inherit-wrapper\n                        v-model="actualConfigData[\'FinSearch.config.shopkey\']"\n                        :inheritedValue="selectedSalesChannelId == null ? null : allConfigs[\'null\'][\'FinSearch.config.shopkey\']"\n                        :customInheritationCheckFunction="checkTextFieldInheritance"\n                >\n                    <template #content="props">\n                        <sw-text-field\n                                name="FinSearch.config.shopkey"\n                                :mapInheritance="props"\n                                :label="$tc(\'findologic.settingForm.config.shopkey.label\')"\n                                :helpText="$tc(\'findologic.settingForm.config.shopkey.tooltipText\')"\n                                :disabled="props.isInherited"\n                                :required="true"\n                                :error="shopkeyErrorState"\n                                :value="props.currentValue"\n                                @change="props.updateCurrentValue">\n                        </sw-text-field>\n                    </template>\n                </sw-inherit-wrapper>\n            {% endblock %}\n\n            {% block findologic_credentials_settings_active %}\n                <sw-inherit-wrapper\n                        v-model="actualConfigData[\'FinSearch.config.active\']"\n                        :inheritedValue="selectedSalesChannelId == null ? null : allConfigs[\'null\'][\'FinSearch.config.active\']"\n                        :customInheritationCheckFunction="checkBoolFieldInheritance"\n                >\n                    <template #content="props">\n                        <sw-switch-field\n                                name="FinSearch.config.active"\n                                :mapInheritance="props"\n                                :label="$tc(\'findologic.settingForm.config.active.label\')"\n                                :helpText="$tc(\'findologic.settingForm.config.active.tooltipText\')"\n                                :disabled="props.isInherited"\n                                :value="props.currentValue"\n                                @change="props.updateCurrentValue"\n                        >\n                        </sw-switch-field>\n                    </template>\n                </sw-inherit-wrapper>\n            {% endblock %}\n\n            {% block findologic_credentials_settings_active_on_category_pages %}\n                <sw-inherit-wrapper\n                        v-model="actualConfigData[\'FinSearch.config.activeOnCategoryPages\']"\n                        :inheritedValue="selectedSalesChannelId == null ? null : allConfigs[\'null\'][\'FinSearch.config.activeOnCategoryPages\']"\n                        :customInheritationCheckFunction="checkBoolFieldInheritance"\n                >\n                    <template #content="props">\n                        <sw-switch-field\n                                name="FinSearch.config.activeOnCategoryPages"\n                                :mapInheritance="props"\n                                :label="$tc(\'findologic.settingForm.config.activeOnCategoryPages.label\')"\n                                :helpText="$tc(\'findologic.settingForm.config.activeOnCategoryPages.tooltipText\')"\n                                :disabled="props.isInherited"\n                                :value="props.currentValue"\n                                @change="props.updateCurrentValue"\n                        >\n                        </sw-switch-field>\n                    </template>\n                </sw-inherit-wrapper>\n            {% endblock %}\n\n            {% block findologic_credentials_settings_test_mode %}\n            <sw-button\n                    v-show="showTestButton"\n                    @click="openSalesChannelUrl"\n            >\n                {{ $tc(\'findologic.settingForm.testButton\') }}\n            </sw-button>\n            <span class="divider"></span>\n            {% endblock %}\n\n            {% block findologic_credentials_settings_search_result_container %}\n            <sw-inherit-wrapper\n                    v-model="actualConfigData[\'FinSearch.config.searchResultContainer\']"\n                    :inheritedValue="selectedSalesChannelId == null ? null : allConfigs[\'null\'][\'FinSearch.config.searchResultContainer\']"\n            >\n                <template #content="props">\n                    <sw-text-field\n                            name="FinSearch.config.searchResultContainer"\n                            :mapInheritance="props"\n                            :label="$tc(\'findologic.settingForm.config.searchResultContainer.label\')"\n                            :helpText="$tc(\'findologic.settingForm.config.searchResultContainer.tooltipText\')"\n                            :disabled="props.isInherited"\n                            :value="props.currentValue"\n                            :placeholder="$tc(\'findologic.settingForm.config.searchResultContainer.placeholder\')"\n                            @change="props.updateCurrentValue"\n                    >\n                    </sw-text-field>\n                </template>\n            </sw-inherit-wrapper>\n            {% endblock %}\n\n            {% block findologic_credentials_settings_navigation_result_container %}\n            <sw-inherit-wrapper\n                    v-model="actualConfigData[\'FinSearch.config.navigationResultContainer\']"\n                    :inheritedValue="selectedSalesChannelId == null ? null : allConfigs[\'null\'][\'FinSearch.config.navigationResultContainer\']"\n            >\n                <template #content="props">\n                    <sw-text-field\n                            name="FinSearch.config.navigationResultContainer"\n                            :mapInheritance="props"\n                            :label="$tc(\'findologic.settingForm.config.navigationResultContainer.label\')"\n                            :helpText="$tc(\'findologic.settingForm.config.navigationResultContainer.tooltipText\')"\n                            :disabled="props.isInherited"\n                            :value="props.currentValue"\n                            :placeholder="$tc(\'findologic.settingForm.config.navigationResultContainer.placeholder\')"\n                            @change="props.updateCurrentValue"\n                    >\n                    </sw-text-field>\n                </template>\n            </sw-inherit-wrapper>\n            {% endblock %}\n\n            {% block findologic_credentials_settings_integration_type %}\n            <sw-inherit-wrapper\n                    v-model="actualConfigData[\'FinSearch.config.integrationType\']"\n                    :inheritedValue="selectedSalesChannelId == null ? null : allConfigs[\'null\'][\'FinSearch.config.integrationType\']"\n            >\n                <template #content="props">\n                    <sw-text-field\n                            name="FinSearch.config.integrationType"\n                            :mapInheritance="props"\n                            :label="$tc(\'findologic.settingForm.config.integrationType.label\')"\n                            :helpText="$tc(\'findologic.settingForm.config.integrationType.tooltipText\')"\n                            :disabled="true"\n                            :value="props.currentValue"\n                    >\n                    </sw-text-field>\n                </template>\n            </sw-inherit-wrapper>\n            {% endblock %}\n        </div>\n        {% endblock %}\n    </sw-container>\n    {% endblock %}\n</sw-card>\n{% endblock %}\n'},Vl01:function(e,n,t){},eRAB:function(e,n){e.exports='{% block findologic %}\n<sw-page class="findologic">\n    {% block findologic_header %}\n    <template slot="smart-bar-header">\n        <h2>\n            {{ $tc(\'sw-settings.index.title\') }}\n            <sw-icon name="small-arrow-medium-right" small></sw-icon>\n            {{ $tc(\'findologic.header\') }}\n        </h2>\n    </template>\n    {% endblock %}\n\n    {% block findologic_actions %}\n    <template slot="smart-bar-actions">\n        {% block findologic_actions_save %}\n        <sw-button\n                class="sw-settings-login-registration__save-action"\n                :isLoading="isLoading"\n                :disabled="isLoading"\n                variant="primary"\n                :title="$tc(\'global.default.save\')"\n                :aria-label="$tc(\'global.default.save\')"\n                v-model="isSaveSuccessful"\n                @click="onSave"\n        >\n            {{ $tc(\'global.default.save\') }}\n        </sw-button>\n        {% endblock %}\n\n        {% block findologic_actions_cancel %}\n        <sw-button\n                :routerLink="{ name: \'sw.settings.index\' }"\n                :title="$tc(\'global.default.cancel\')"\n                :aria-label="$tc(\'global.default.cancel\')">\n            {{ $tc(\'global.default.cancel\') }}\n        </sw-button>\n        {% endblock %}\n    </template>\n    {% endblock %}\n\n    {% block findologic_content %}\n    <template slot="content">\n        {% block findologic_content_card %}\n        <sw-card-view>\n            {% block findologic_content_card_channel_config %}\n            <sw-sales-channel-config\n                    ref="configComponent"\n                    domain="FinSearch.config"\n                    v-model="config">\n                {% block findologic_content_card_channel_config_sales_channel %}\n                <template #select="{ onInput, selectedSalesChannelId, salesChannel }">\n                    {% block findologic_content_card_channel_config_sales_channel_card %}\n                    <sw-card title="Sales Channel">\n                        {% block findologic_content_card_channel_config_sales_channel_card_title %}\n                        <sw-single-select\n                                class="fl-sales-channel-field"\n                                v-model="selectedSalesChannelId"\n                                labelProperty="translated.name"\n                                valueProperty="id"\n                                :options="salesChannel"\n                                @input="onInput">\n                        </sw-single-select>\n                        {% endblock %}\n                    </sw-card>\n                    {% endblock %}\n                </template>\n                {% endblock %}\n\n                {% block findologic_content_card_channel_config_cards %}\n                <template #content="{ actualConfigData, allConfigs, selectedSalesChannelId }">\n                    <div v-if="actualConfigData">\n                        {% block findologic_content_card_channel_config_credentials_card %}\n                        <findologic-config\n                                :isActive="isActive"\n                                :shopkeyAvailable="shopkeyAvailable"\n                                :isValidShopkey="isValidShopkey"\n                                :isStagingShop="isStagingShop"\n                                :actualConfigData="actualConfigData"\n                                :allConfigs="allConfigs"\n                                :shopkeyErrorState="shopkeyErrorState"\n                                :selectedSalesChannelId="selectedSalesChannelId"></findologic-config>\n                        {% endblock %}\n                    </div>\n                    <sw-loader v-if="!actualConfigData"></sw-loader>\n                </template>\n                {% endblock %}\n            </sw-sales-channel-config>\n            {% endblock %}\n\n            {% block findologic_content_card_loading %}\n            <sw-loader v-if="isLoading">\n            </sw-loader>\n            {% endblock %}\n        </sw-card-view>\n        {% endblock %}\n    </template>\n    {% endblock %}\n</sw-page>\n{% endblock %}\n'}},[["HerF","runtime","vendors-node"]]]);