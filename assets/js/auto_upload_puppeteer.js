const puppeteer = require('./puppeteer');

(async () => {
    const uploadFilePath = process.argv[2];

    if(uploadFilePath !== undefined) {
        const browser = await puppeteer.launch();
        const page = await browser.newPage();

        const username = 'Karina@localliving.dk';
        const password = 'Ferieigen2023';

        await page.goto('https://localliving.itravelsoftware.com/itravel/admin/Import/ImportData.aspx?vrsta=4', {waitUntil: 'load', timeout: 600000});
        await page.type('#Login1_UserName', username);
        await page.type('#Login1_Password', password);
        await page.click('#Login1_LoginButton');

        await page.waitForSelector('input#ctl00_ctl00_i_Main_fileNameRadUploadfile0');

        const elementHandle = await page.$('input#ctl00_ctl00_i_Main_fileNameRadUploadfile0');

        await elementHandle.uploadFile(uploadFilePath);

        await page.click('input#ctl00_ctl00_i_Main_unesiButton');

        await page.waitForSelector('input#ctl00_ctl00_i_Main_unesiButton');

        await page.click('input#ctl00_ctl00_i_Main_unesiButton');

        await page.waitForSelector('#errorNotificationContainer');

        await console.log("success");

        await browser.close();
    }
})();