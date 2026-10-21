import { expect, it } from 'vitest';

const vueModules = import.meta.glob('../src/**/*.vue', { eager: true });
const componentEntries = import.meta.glob('../src/**/index.js', { eager: true });

it('transforms every Vue source module for coverage collection', () => {
    expect(Object.keys(vueModules).length).toBeGreaterThan(0);
    expect(Object.keys(componentEntries).length).toBeGreaterThan(0);
});
