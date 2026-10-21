import { defineComponent, h, inject, nextTick } from 'vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Alert from '../src/spa/components/ui/alert/Alert.vue';
import Button from '../src/spa/components/ui/button/Button.vue';
import Card from '../src/spa/components/ui/card/Card.vue';
import Input from '../src/spa/components/ui/input/Input.vue';
import Separator from '../src/spa/components/ui/separator/Separator.vue';
import Sheet from '../src/spa/components/ui/sheet/Sheet.vue';
import SheetClose from '../src/spa/components/ui/sheet/SheetClose.vue';
import SheetContent from '../src/spa/components/ui/sheet/SheetContent.vue';
import SheetDescription from '../src/spa/components/ui/sheet/SheetDescription.vue';
import SheetFooter from '../src/spa/components/ui/sheet/SheetFooter.vue';
import SheetHeader from '../src/spa/components/ui/sheet/SheetHeader.vue';
import SheetOverlay from '../src/spa/components/ui/sheet/SheetOverlay.vue';
import SheetTitle from '../src/spa/components/ui/sheet/SheetTitle.vue';
import SheetTrigger from '../src/spa/components/ui/sheet/SheetTrigger.vue';
import Sidebar from '../src/spa/components/ui/sidebar/Sidebar.vue';
import SidebarProvider from '../src/spa/components/ui/sidebar/SidebarProvider.vue';
import Skeleton from '../src/spa/components/ui/skeleton/Skeleton.vue';
import Textarea from '../src/spa/components/ui/textarea/Textarea.vue';
import Tooltip from '../src/spa/components/ui/tooltip/Tooltip.vue';
import TooltipContent from '../src/spa/components/ui/tooltip/TooltipContent.vue';
import TooltipProvider from '../src/spa/components/ui/tooltip/TooltipProvider.vue';
import TooltipTrigger from '../src/spa/components/ui/tooltip/TooltipTrigger.vue';

const primitiveStubs = {
    DialogRoot: { props: ['open'], template: '<div data-dialog-root :data-open="open"><slot :open="open" /></div>' },
    DialogClose: { template: '<button data-dialog-close><slot /></button>' },
    DialogContent: { template: '<div data-dialog-content><slot /></div>' },
    DialogPortal: { template: '<div data-dialog-portal><slot /></div>' },
    DialogOverlay: { template: '<div data-dialog-overlay><slot /></div>' },
    DialogDescription: { template: '<div data-dialog-description><slot /></div>' },
    DialogTitle: { template: '<div data-dialog-title><slot /></div>' },
    DialogTrigger: { template: '<button data-dialog-trigger><slot /></button>' },
    TooltipRoot: { template: '<div data-tooltip-root><slot /></div>' },
    TooltipProvider: { template: '<div data-tooltip-provider><slot /></div>' },
    TooltipContent: { template: '<div data-tooltip-content><slot /></div>' },
    TooltipPortal: { template: '<div data-tooltip-portal><slot /></div>' },
    TooltipArrow: { template: '<span data-tooltip-arrow />' },
    TooltipTrigger: { template: '<button data-tooltip-trigger><slot /></button>' },
};

const stubs = { ...primitiveStubs, SheetOverlay: { template: '<div data-sheet-overlay />' }, Cross2Icon: { template: '<svg />' } };

describe('small presentation components', () => {
    it('renders alert, card, skeleton, and button variants', () => {
        const alert = mount(Alert, { props: { variant: 'destructive', class: 'extra' }, slots: { default: 'Warning' } });
        expect(alert.attributes('data-slot')).toBe('alert');
        expect(alert.classes()).toContain('extra');
        expect(alert.classes().join(' ')).toContain('border-destructive');

        const card = mount(Card, { props: { as: 'article', class: ['wide'] }, slots: { default: 'Content' } });
        expect(card.element.tagName).toBe('ARTICLE');
        expect(card.classes()).toContain('wide');
        expect(mount(Skeleton, { props: { class: { compact: true } } }).classes()).toContain('compact');

        const button = mount(Button, { props: { variant: 'secondary', size: 'sm', as: 'a' }, attrs: { href: '/next' }, slots: { default: 'Continue' }, global: { stubs } });
        expect(button.element.tagName).toBe('A');
        expect(button.attributes('data-variant')).toBe('secondary');
        expect(button.attributes('data-size')).toBe('sm');
        expect(button.attributes('href')).toBe('/next');
    });

    it('uses controlled and fallback values and emits input changes', async () => {
        const input = mount(Input, { props: { defaultValue: 'fallback', class: 'compact' } });
        expect(input.element.value).toBe('fallback');
        expect(input.classes()).toContain('compact');
        await input.setValue('changed');
        expect(input.emitted('update:modelValue')).toEqual([['changed']]);
        await input.setProps({ modelValue: 'controlled' });
        expect(input.element.value).toBe('controlled');

        const textarea = mount(Textarea, { props: { defaultValue: 'initial', class: 'large' } });
        expect(textarea.element.value).toBe('initial');
        await textarea.setValue('edited');
        expect(textarea.emitted('update:modelValue')).toEqual([['edited']]);
        await textarea.setProps({ modelValue: 'controlled' });
        expect(textarea.element.value).toBe('controlled');
    });
});

describe('sheet and tooltip adapters', () => {
    it('forwards sheet state, slots, props, and side variants', () => {
        const wrapper = mount(Sheet, { props: { open: true }, slots: { default: 'inside' }, global: { stubs } });
        expect(wrapper.find('[data-dialog-root]').attributes('data-open')).toBe('true');
        expect(wrapper.text()).toContain('inside');

        for (const component of [SheetClose, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger]) {
            const child = mount(component, { props: { class: 'custom' }, slots: { default: 'slot' }, global: { stubs } });
            expect(child.text()).toContain('slot');
        }
        expect(mount(SheetOverlay, { props: { class: 'custom' }, global: { stubs } }).exists()).toBe(true);

        for (const side of ['right', 'left', 'top', 'bottom']) {
            const content = mount(SheetContent, { props: { side, class: 'custom' }, slots: { default: 'body' }, global: { stubs } });
            expect(content.find('[data-dialog-content]').classes().join(' ')).toContain('custom');
            expect(content.text()).toContain('body');
        }
    });

    it('forwards tooltip props, events, content, and provider defaults', () => {
        const provider = mount(TooltipProvider, { slots: { default: 'child' }, global: { stubs } });
        expect(provider.text()).toContain('child');
        expect(mount(Tooltip, { props: { open: true }, slots: { default: 'trigger' }, global: { stubs } }).text()).toContain('trigger');
        expect(mount(TooltipTrigger, { props: { asChild: true }, slots: { default: 'trigger' }, global: { stubs } }).text()).toContain('trigger');
        const content = mount(TooltipContent, { props: { side: 'top', class: 'custom' }, slots: { default: 'hint' }, global: { stubs } });
        expect(content.text()).toContain('hint');
        expect(content.find('[data-tooltip-arrow]').exists()).toBe(true);
    });
});

describe('sidebar state', () => {
    it('provides default, controlled, close, toggle, and watch behavior', async () => {
        const Controls = defineComponent({
            setup() {
                const sidebar = inject('sidebar');
                return () => h('div', [
                    h('button', { id: 'toggle', onClick: sidebar.toggle }, 'toggle'),
                    h('button', { id: 'close', onClick: sidebar.close }, 'close'),
                    h('button', { id: 'open', onClick: () => sidebar.setOpen(true) }, 'open'),
                    h(Sidebar, { default: true }, { default: () => 'nav' }),
                ]);
            },
        });
        const wrapper = mount(SidebarProvider, { props: { defaultOpen: false }, slots: { default: () => h(Controls) } });
        expect(wrapper.find('#application-sidebar').attributes('aria-hidden')).toBe('true');
        await wrapper.get('#toggle').trigger('click');
        expect(wrapper.find('#application-sidebar').attributes('data-state')).toBe('open');
        await wrapper.get('#close').trigger('click');
        expect(wrapper.find('#application-sidebar').attributes('inert')).toBeDefined();
        await wrapper.get('#open').trigger('click');
        expect(wrapper.emitted('update:open')).toEqual([[true], [false], [true]]);

        const controlled = mount(SidebarProvider, { props: { open: true }, slots: { default: () => h(Controls) } });
        await controlled.setProps({ open: false });
        await nextTick();
        expect(controlled.find('#application-sidebar').attributes('data-state')).toBe('closed');
    });
});
