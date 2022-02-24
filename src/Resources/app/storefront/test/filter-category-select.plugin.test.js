/**
 * @jest-environment jsdom
 */

import FilterCategorySelectPlugin from '../src/js/filter-category-select.plugin';
import FilterCategorySelectElement from './filter-category-select-helper';
import Iterator from 'src/helper/iterator.helper';
import ListingPlugin
    from '../../../../../../../../vendor/shopware/platform/src/Storefront/Resources/app/storefront/src/plugin/listing/listing.plugin';

describe('filter-category-select.plugin.js', () => {
    let filterCategorySelectPlugin;
    let filterCategorySelectElement;

    beforeEach(() => {
        window.csrf = {
            enabled: false
        };

        window.router = [];

        const mockElement = document.createElement('div');

        const cmsElementProductListingWrapper = document.createElement('div');
        cmsElementProductListingWrapper.classList.add('cms-element-product-listing-wrapper');

        const mockElementSpan = document.createElement('span');
        mockElementSpan.classList.add('filter-multi-select-count');

        const mockElementButton = document.createElement('button');
        mockElementButton.classList.add('filter-panel-item-toggle');
        filterCategorySelectElement = new FilterCategorySelectElement();

        mockElement.appendChild(cmsElementProductListingWrapper);
        mockElement.appendChild(mockElementButton);
        mockElement.appendChild(mockElementSpan);
        mockElement.appendChild(filterCategorySelectElement.createCategoryStructure());

        document.body.appendChild(mockElement);

        window.PluginManager = {
            getPluginInstancesFromElement: () => {
                return new Map();
            },
            getPlugin: () => {
                return {
                    get: () => []
                };
            },
            getPluginInstanceFromElement: () => {
                return new ListingPlugin(mockElement);
            },
        };

        filterCategorySelectPlugin = new FilterCategorySelectPlugin(mockElement);
    });

    afterEach(() => {
        filterCategorySelectPlugin = null;
    });

    test('filter category select plugin exists', () => {
        expect(typeof filterCategorySelectPlugin).toBe('object');
    });

    test('On initialization only main parent categories should be visible, all child categories must be hidden', () => {

        let mainParentsVisible;
        let childsVisible;
        let categories = document.querySelectorAll('.category-filter-container');
        let subCategories = document.querySelectorAll('.sub-item');

        for (let i = 0; i < categories.length; i++) {
            mainParentsVisible = categories[i].classList.contains('category-filter-container');
        }
        for (let i = 0; i < subCategories.length; i++) {
            childsVisible = subCategories[i].classList.contains('show-category-list-item');
        }
        expect(mainParentsVisible).toBe(true);
        expect(childsVisible).not.toBe(true);

    });

    test('On select Men Category: sub-categories visible on their first level', () => {
        let men = document.querySelector('#Men');
        const expectedClass = 'show-category-list-item';
        const clickEvent = new Event('click');
        let child = men.parentNode.querySelectorAll('.sub-item');
        men.addEventListener('click', () => {
            men.checked = !men.checked;
            men.dispatchEvent(new Event('change'));
        });
        men.dispatchEvent(new Event('click'));

        let isUrlUpdated = window.location.search.indexOf('Men') !== -1;
        let childs = filterCategorySelectPlugin.getSiblingsCategories(child[0]);
        let childsVisible;

        for (let i = 0; i < childs.length; i++) {
            childsVisible = childs[i].classList.contains(expectedClass);
            if (!childs) {
                break;
            }
        }

        let grandChilds = childs[0].querySelector('.sub-item');
        let grandChildsVisible = grandChilds.classList.contains('hide-category-list-item');

        expect(men.checked).toBe(true);
        expect(childsVisible).toBe(true);
        expect(grandChildsVisible).toBe(true)
        expect(isUrlUpdated).toBe(true);
    });

    test('All Parent Categories has icon', () => {
        let checkboxes = document.querySelectorAll('.filter-category-select-checkbox');
        let bothExist;
        for (let i = 0; i < checkboxes.length; i++) {
            let categoryParent = checkboxes[i].parentNode;
            let isSubCategoryExist = categoryParent.querySelector('.sub-item');
            let isToggleIconExist = categoryParent.querySelector('.category-toggle-icon');

            bothExist = isSubCategoryExist !== (!isToggleIconExist);
            if (!bothExist) {
                break;
            }
        }
        expect(bothExist).toBe(true);
    });

    test('Selecting Newcomer, updates the url accordingly', () => {
        let checkbox = document.querySelector('#Newcomers');
        const clickEvent = new Event('click');
        const changeEvent = new Event('change');
        checkbox.addEventListener('click', () => {
            checkbox.checked = !checkbox.checked;
            checkbox.dispatchEvent(changeEvent);
        });
        checkbox.dispatchEvent(clickEvent);

        let isUrlUpdated = window.location.search.indexOf('Newcomers') !== -1;
        expect(isUrlUpdated).toBe(true);
    });

    test('On click women Category Icon: sub-categories visible, no update in url, checkbox not selected', () => {
        let isShowClassExist;
        let womenCategoryToggleIcon = document.querySelectorAll('.category-toggle-icon')[2];
        const clickEvent = new Event('click');
        womenCategoryToggleIcon.dispatchEvent(clickEvent);

        let womenSubCategory = womenCategoryToggleIcon.parentNode.querySelector('.sub-item');
        let womenSubSiblings = filterCategorySelectPlugin.getSiblingsCategories(womenSubCategory);
        womenSubSiblings.push(womenSubCategory);
        for (let i = 0; i < womenSubSiblings.length; i++) {
            isShowClassExist = womenSubSiblings[i].classList.contains('show-category-list-item');
            if (!isShowClassExist) {
                break;
            }
        }

        let isUrlUpdated = window.location.search.indexOf('Women') !== -1;
        let isCategorySelected = document.getElementById('Women').checked;
        let areMoreDeepVisible = womenSubSiblings[0].querySelectorAll('show-category-list-item').length;

        expect(isShowClassExist).toBe(true);
        expect(isUrlUpdated).not.toBe(true);
        expect(isCategorySelected).not.toBe(true)
        expect(areMoreDeepVisible).not.toBe(true);

    });

    test('Women and hats categories to be selected', () => {
        let checkbox = document.querySelector('#Women_Hats');
        const clickEvent = new Event('click');
        checkbox.addEventListener('click', () => {
            checkbox.checked = !checkbox.checked;
            checkbox.dispatchEvent(new Event('change'));
        });
        checkbox.dispatchEvent(clickEvent);

        let isCategorySelected = document.getElementById('Women').checked;
        let isWomenHat = checkbox.checked;
        let params = window.location.search;
        let isUrlUpdated = params.indexOf('Women') !== -1 && params.indexOf('Women_Hats') !== -1;
        expect(isCategorySelected).toBe(true);
        expect(isWomenHat).toBe(true);
        expect(isUrlUpdated).toBe(true);
    });

    test('Seleting Men category, click on an icon to hide sub categories', () => {

        let men = document.querySelector('#Men');
        let child = men.parentNode.querySelector('.sub-item');
        const subCategory = child;
        const icon = document.querySelector('.category-toggle-icon');
        icon.dispatchEvent(new Event('click'));
        let isChildHide = subCategory.classList.contains('hide-category-list-item');
        expect(isChildHide).toBe(true);
    });

    test('Ensure that deselecting an already selected category, will automatically hide all subcategories', () => {
        const icon = document.querySelector('.category-toggle-icon');
        // To show Subcategories, so that they can be hidden by deselecting parent
        icon.dispatchEvent(new Event('click'));

        let category = document.querySelector('#Men');
        let subCategory = category.parentNode.querySelector('.sub-item');
        let isVisible = subCategory.classList.contains('show-category-list-item');

        expect(isVisible).toBe(true);
        category.addEventListener('click', () => {
            category.checked = !category.checked;
        });
        category.dispatchEvent(new Event('click'));
        isVisible = subCategory.classList.contains('show-category-list-item');
        expect(isVisible).not.toBe(true);
    });

    test('Disable Inactive Filters', () => {
        let filters =
            {
                'cat':
                    {
                        'entities':
                            [
                                {
                                    'translated': {'name': 'cat'},
                                    'options': [
                                        {'id': 'Men', 'translated': {'name': 'Men'}},
                                        {'id': 'Hats', 'translated': {'name': 'Hats'}},
                                        {'id': 'Shoes', 'translated': {'name': 'Shoes'}},
                                        {'id': 'Shirts', 'translated': {'name': 'Shirts'}},
                                        {'id': 'Cool Hats', 'translated': {'name': 'Cool Hats'}},
                                        {'id': 'Lame Hats', 'translated': {'name': 'Lame Hats'}}]
                                }
                            ]
                    }, 'vendor':
                    {'entities': []},
                'price': {'entities': []},
                'color': {'entities': []},
                'shipping_free': {'entities': []},
                'shoe-color': {'entities': []},
                'textile': {'entities': []},
                'width': {'entities': []}
            };
        filterCategorySelectPlugin.options.name = 'cat';
        filterCategorySelectPlugin.refreshDisabledState(filters);
        let checkboxes = document.querySelectorAll('.filter-category-select-checkbox');
        let isDisable;
        let activeItems = [];
        const properties = filters['cat'];
        const entities = properties.entities;
        const property = entities.find(entity => entity.translated.name === 'cat');
        if (property) {
            activeItems.push(...property.options);
        }
        let activeIds = activeItems.map(entity => entity.id);
        let disabledChecked = false;
        Iterator.iterate(checkboxes, (checkbox) => {
            let notActiveId = !activeIds.includes(checkbox.id);
            isDisable = notActiveId ? checkbox.disabled : false;
            if (!isDisable) {
                return isDisable;
            }
            checkbox.dispatchEvent(new Event('click'));
            disabledChecked = checkbox.checked;
        });
        expect(isDisable).toBe(true);


    })

});
