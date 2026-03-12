import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import path from 'node:path';

const baseURL = 'http://127.0.0.1:8002';
const spidOne = '1234567890';
const spidTwo = '2234567890';
const mecOneAddressA = 'MEC1 Test Address A';
const mecOneAddressB = 'MEC1 Test Address B';
const mecOneMapA = 'https://maps.example.com/mec1-a';
const mecOneMapB = 'https://maps.example.com/mec1-b';

const laravelEnv = {
    ...process.env,
    APP_ENV: 'playwright',
    APP_URL: baseURL,
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: path.join(process.cwd(), 'database', 'playwright.sqlite'),
    CACHE_STORE: 'file',
    SESSION_DRIVER: 'file',
    QUEUE_CONNECTION: 'sync',
    MAIL_MAILER: 'log',
};

function tinkerJson(expression) {
    const output = execFileSync(
        'php',
        ['artisan', 'tinker', '--execute', `echo json_encode(${expression});`],
        {
            cwd: process.cwd(),
            env: laravelEnv,
            encoding: 'utf8',
        },
    ).trim();

    return output ? JSON.parse(output) : null;
}

async function loginAsAdmin(page) {
    await page.goto('/login');
    await page.getByPlaceholder('Email').fill('admin@example.com');
    await page.getByPlaceholder('Password').fill('admin123');
    await page.getByRole('button', { name: /sign in/i }).click();
    await expect(page).toHaveURL(/\/dashboard$/);
}

async function updateBranchLocation(page, branchName, address, mapLink) {
    await page.goto('/category');
    const branchRow = page.locator('tr').filter({ hasText: branchName }).first();
    await expect(branchRow).toBeVisible();
    await branchRow.getByRole('link', { name: /edit/i }).click();
    await expect(page.locator('input[name="city"]')).toHaveValue(/.*/);
    await page.locator('input[name="city"]').fill('Jeddah');
    await page.locator('input[name="address"]').fill(address);
    await page.locator('input[name="map_link"]').fill(mapLink);
    await page.getByRole('button', { name: /^update$/i }).click();
    await expect(page.locator('.alert-success')).toContainText(/updated successfully/i);
}

async function moveToConfirmationStep(page, branchName) {
    await page.goto('/');
    await expect(page.getByRole('heading', { name: /core booking system/i })).toBeVisible();

    await page.locator('.category-card').filter({ hasText: branchName }).first().click();
    await page.locator('#next-step').click();

    const serviceCard = page.locator('.service-card').first();
    await expect(serviceCard).toBeVisible();
    await serviceCard.click();
    await page.locator('#next-step').click();

    const employeeCard = page.locator('.employee-card').first();
    await expect(employeeCard).toBeVisible();
    await employeeCard.click();
    await page.locator('#next-step').click();

    await selectFirstAvailableDateAndSlot(page);
    await page.locator('#next-step').click();
    await expect(page.getByRole('heading', { name: /confirm your booking/i })).toBeVisible();
}

async function selectFirstAvailableDateAndSlot(page) {
    const availableDays = page.locator('#calendar-body .calendar-day:not(.disabled)');
    const dayCount = await availableDays.count();

    for (let index = 0; index < dayCount; index += 1) {
        await availableDays.nth(index).click();
        const firstSlot = page.locator('.time-slot').first();

        try {
            await firstSlot.waitFor({ state: 'visible', timeout: 3000 });
            await firstSlot.click();
            return;
        } catch {
            // Try the next visible day.
        }
    }

    throw new Error('No available booking slot found in the visible calendar range.');
}

async function completeBooking(page, branchName, booking) {
    await moveToConfirmationStep(page, branchName);

    await page.locator('#spid').fill(booking.spid);
    await page.locator('#sample_person_name').fill(booking.participant);
    await page.locator('#mobile_number').fill(booking.mobile);
    await page.locator('#visit_stage').selectOption(booking.visitStage);
    await page.locator('#interviewer_id').fill(booking.interviewerId);
    await page.locator('#supervisor_id').fill(booking.supervisorId);
    await page.locator('#customer-notes').fill(booking.notes);

    await page.locator('#next-step').click();
    await expect(page.locator('#bookingSuccessModal')).toBeVisible();
    await expect(page.locator('#modal-booking-details')).toContainText(booking.spid);
    await expect(page.locator('#modal-booking-details')).toContainText(booking.participant);
    await page.locator('#bookingSuccessModal .modal-footer button').click();
    await expect(page.getByRole('heading', { name: /select a branch/i })).toBeVisible();
}

test.describe('MEC document flows', () => {
    test('covers the implemented MEC flows outside clarification-only items and role scenarios', async ({ page }) => {
        page.on('dialog', (dialog) => dialog.accept());

        await loginAsAdmin(page);

        await page.goto('/category/create');
        await expect(page.locator('input[name="city"]')).toBeVisible();
        await expect(page.locator('input[name="address"]')).toBeVisible();
        await expect(page.locator('input[name="map_link"]')).toBeVisible();

        await page.goto('/service/create');
        await expect(page.getByText('Sale Price')).toHaveCount(0);
        await expect(page.getByText(/^Price$/)).toHaveCount(0);

        await updateBranchLocation(page, 'MEC1', mecOneAddressA, mecOneMapA);

        await moveToConfirmationStep(page, 'MEC1');
        await expect(page.locator('#customer-email')).toHaveCount(0);
        await expect(page.locator('#summary-price')).toHaveCount(0);
        await page.locator('#spid').fill(spidOne);
        await page.locator('#sample_person_name').fill('Participant One');
        await page.locator('#mobile_number').fill('abc123');
        const mobileIsValid = await page.locator('#mobile_number').evaluate((element) => element.checkValidity());
        expect(mobileIsValid).toBe(false);
        await page.goto('/dashboard');

        await completeBooking(page, 'MEC1', {
            spid: spidOne,
            participant: 'Participant One',
            mobile: '0501234567',
            visitStage: 'second_visit',
            interviewerId: 'inter20',
            supervisorId: 'sv01',
            notes: 'Initial MEC booking',
        });

        await updateBranchLocation(page, 'MEC1', mecOneAddressB, mecOneMapB);

        await completeBooking(page, 'MEC1', {
            spid: spidOne,
            participant: 'Participant One',
            mobile: '0501234567',
            visitStage: 'third_visit',
            interviewerId: 'inter20',
            supervisorId: 'sv01',
            notes: 'Follow-up MEC booking',
        });

        await completeBooking(page, 'MEC2', {
            spid: spidTwo,
            participant: 'Participant Two',
            mobile: '0509876543',
            visitStage: 'second_visit',
            interviewerId: 'inter30',
            supervisorId: 'sv02',
            notes: 'Different branch control booking',
        });

        const snapshotRecords = tinkerJson(`App\\Models\\Appointment::where('spid', '${spidOne}')->orderBy('id')->get(['spid', 'visit_stage', 'branch_address_snapshot', 'branch_map_link_snapshot'])->toArray()`);
        expect(snapshotRecords).toHaveLength(2);
        expect(snapshotRecords[0].branch_address_snapshot).toBe(mecOneAddressA);
        expect(snapshotRecords[0].branch_map_link_snapshot).toBe(mecOneMapA);
        expect(snapshotRecords[1].branch_address_snapshot).toBe(mecOneAddressB);
        expect(snapshotRecords[1].branch_map_link_snapshot).toBe(mecOneMapB);

        await page.goto('/appointments');
        await expect(page.locator('#myTable thead')).toContainText('SPID');
        await expect(page.locator('#myTable thead')).toContainText('Branch');
        await expect(page.locator('#myTable thead')).toContainText('Visit');
        await expect(page.locator('#myTable tbody tr').filter({ hasText: spidOne })).toHaveCount(2);
        await expect(page.locator('#myTable')).not.toContainText('Amount');
        await expect(page.locator('#myTable')).not.toContainText('Email');

        const firstParticipantRow = page.locator('#myTable tbody tr').filter({ hasText: spidOne }).first();
        await firstParticipantRow.getByRole('button', { name: /view/i }).click();
        await expect(page.locator('#appointmentModal')).toBeVisible();
        await expect(page.locator('#modalSpid')).toHaveText(spidOne);
        await expect(page.locator('#modalInterviewerId')).toHaveText('inter20');
        await expect(page.locator('#modalSupervisorId')).toHaveText('sv01');
        await expect(page.locator('#modalBranch')).toHaveText('MEC1');
        await expect(page.locator('#modalStatusSelect option').filter({ hasText: 'Pending payment' })).toHaveCount(0);
        await page.locator('#appointmentModal .modal-footer .btn-secondary').click();

        await page.goto('/category');
        const mecOneRow = page.locator('tr').filter({ hasText: 'MEC1' }).first();
        await mecOneRow.getByRole('link', { name: /report/i }).click();
        await expect(page.getByRole('heading', { name: /branch report: mec1/i })).toBeVisible();
        await expect(page.locator('table')).toContainText('Appointment Date');
        await expect(page.locator('table')).toContainText('Interviewer ID');
        await expect(page.locator('table')).toContainText('Supervisor ID');
        await expect(page.locator('table')).toContainText(spidOne);
        await expect(page.locator('table')).not.toContainText(spidTwo);
    });
});
