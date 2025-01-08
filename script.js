let course = document.getElementById("course").title;
let start_year = document.getElementById("start_year").title;
let end_year = document.getElementById("end_year").title;
let start_month = document.getElementById("start_month").title;
let end_month = document.getElementById("end_month").title;
let start_day = document.getElementById("start_day").title;
let end_day = document.getElementById("end_day").title;
let start_hour = document.getElementById("start_hour").title;
let end_hour = document.getElementById("end_hour").title;
let start_minute = document.getElementById("start_minute").title;
let end_minute = document.getElementById("end_minute").title;
const start_time = convertToUnixTime(start_year, start_month, start_day, start_hour, start_minute);
const end_time = convertToUnixTime(end_year, end_month, end_day, end_hour, end_minute);
const ydata = d3.json("./yscalejson.php?course=" + course);
const log = d3.json("./logjson.php?course=" + course + "&start_time=" + start_time + "&end_time=" + end_time);

//unixtimeに変換する関数
function convertToUnixTime(year, month, day, hour, minute) {
    // JavaScriptのDateオブジェクトは月を0から11で扱うため、月を1引く
    const date = new Date(year, month - 1, day, hour, minute);
    // UNIX時間に変換
    return Math.floor(date.getTime() / 1000);
}

// JSONデータを2つ取得してから処理を行う
Promise.all([ydata, log])
    .then(function ([data1, data2]) {

        // データ2に基づいて2つ目のSVGを描画
        drawChart(data2, data1, "#chart1");
    })
    .catch(function (error) {
        console.error('Error loading the data:', error);
    });

function drawChart(data2, data1, chartId) {
    function extractFirstSpan(html) {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const firstSpan = doc.querySelector('span[lang="ja"]');
        let text = firstSpan ? firstSpan.textContent : doc.body.textContent;
        return text.length > 20 ? text.substring(0, 20) + "..." : text;
    }
    const margin = { top: 40, bottom: 40, left: 210 };
    const width = 1400 - margin.left;
    const height = 1200 - margin.top - margin.bottom;

    // y軸用のスケールを定義
    const yScale = d3.scalePoint()
        .domain(data1.map((d, i) => i))
        .range([0, height])
        .padding(0);

    function unixToDate(unixTimestamp) {
        return new Date(unixTimestamp * 1000);
    }

    const startDate = unixToDate(start_time);
    const endDate = unixToDate(end_time);

    // x軸用のスケールを定義
    const xScale = d3.scaleTime()
        .domain([startDate, endDate])
        .range([0, width]);

    // ズーム機能の定義
    const zoom = d3.zoom()
        .scaleExtent([1, 20]) // ズームの範囲を1倍から20倍に制限
        .extent([[0, 0], [width, height]])
        .on("zoom", zoomed);

    // クリップパスを定義
    const svg = d3.select(chartId)
        .append("svg")
        .attr("width", width + margin.left)
        .attr("height", height + margin.top + margin.bottom)
        .call(zoom);

    // クリップパスを追加
    svg.append("defs")
        .append("clipPath")
        .attr("id", "clip")
        .append("rect")
        .attr("y", -margin.top / 2)
        .attr("width", width)
        .attr("height", height + margin.top);

    const g = svg.append("g")
        .attr("transform", `translate(${margin.left},${margin.top})`);

    // メインのグループにクリップパスを適用
    const chartGroup = g.append("g")
        .attr("clip-path", "url(#clip)");

    const tooltip = chartGroup.append("g")
        .style("pointer-events", "none") // ツールチップがマウスイベントを受けないようにする
        .style("opacity", 0); // 初期状態では非表示
    
    tooltip.append("rect")
        .attr("x", 0)
        .attr("y", 0)
        .attr("width", 120)
        .attr("height", 30)
        .attr("rx", 5)
        .attr("ry", 5)
        .attr("fill", "white")
        .attr("fill-opacity",1)
        .attr("stroke", "gray");
    
    tooltip.append("text")
        .attr("x", 10)
        .attr("y", 20)
        .attr("font-size", "12px")
        .attr("fill", "black");

    // グリッドラインを描画
    function drawGrid() {
        chartGroup.selectAll(".grid-line").remove();
        chartGroup.selectAll(".grid-line")
            .data(data1.map((d, i) => i))
            .enter()
            .append("line")
            .attr("class", "grid-line")
            .attr("x1", 0)
            .attr("x2", width)
            .attr("y1", d => yScale(d))
            .attr("y2", d => yScale(d))
            .attr("stroke", (d, i) => i % 2 === 0 ? "lightgray" : "gray")
            .attr("stroke-dasharray", (d, i) => i % 2 === 0 ? "2,2" : "0");
    }

    // 軸を描画
    const xAxis = g.append("g")
        .attr("class", "x-axis")
        .attr("transform", `translate(0,${height})`)
        .call(d3.axisBottom(xScale)
            .tickFormat(d3.timeFormat("%H:%M")));

    const yAxis = g.append("g")
        .attr("class", "y-axis")
        .call(d3.axisLeft(yScale).tickFormat(i => extractFirstSpan(data1[i])));

    drawGrid();

    // 折れ線生成器
    const lineGenerator = d3.line()
        .x(d => xScale(new Date(d.timecreated * 1000)))
        .y(d => {
            const parsedName = stripHTMLTags(d.name);
            const index = data1.findIndex(item => {
                const parsedItem = stripHTMLTags(item);
                return parsedItem.includes(parsedName);
            });
            return yScale(index);
        })
        .curve(d3.curveLinear);

    function stripHTMLTags(html) {
        return html.replace(/<\/?[^>]+(>|$)/g, "");
    }

    // ユーザーごとのデータを描画
    Object.keys(data2).forEach(userId => {
        const userLog = data2[userId];

        // 折れ線描画
        const linePath = chartGroup.append("path")
            .datum(userLog)
            .attr("class", `user-line user-line-${userId}`)
            .attr("fill", "none")
            .attr("stroke", "red")
            .attr("stroke-width", 2)
            .attr("d", lineGenerator)
            .attr("opacity", 0.2)

            .on("click", function () {
                chartGroup.selectAll(".user-line")
                    .attr("opacity", 0.2)
                    .attr("stork-width", 2);

                chartGroup.selectAll(".user-dot")
                    .attr("opacity", 0.2)
                    .attr("r", 4);

                d3.select(this)
                    .attr("opacity", 1)
                    .attr("stroke-width", 4);

                chartGroup.selectAll(`.dot-${userId}`)
                    .attr("opacity", 1)
                    .attr("r", 6);
            })
            .on("mouseover",(event,d) =>{
                console.log("mouseover event",`${userId}`);

                tooltip.raise();
                
                tooltip.style("opacity",1)
                    .select("text")
                    .text(`${userId}`);
            })
            .on("mousemove",(event)=> {
                const [mouseX,mouseY] = d3.pointer(event);

                tooltip.attr("transform",`translate(${mouseX + 10}, ${mouseY - 20})`);
            })
            .on("mouseout",()=> {
                console.log("mouseout")
                tooltip.style("opacity",0);
            });

        // 点を描画
        const dots = chartGroup.selectAll(".dot-" + userId)
            .data(userLog)
            .enter()
            .append("circle")
            .attr("class", "user-dot dot-" + userId)
            .attr("cx", d => xScale(new Date(d.timecreated * 1000)))
            .attr("cy", d => {
                const parsedName = stripHTMLTags(d.name);
                const index = data1.findIndex(item => {
                    const parsedItem = stripHTMLTags(item);
                    return parsedItem.includes(parsedName);
                });
                return yScale(index);
            })
            .attr("r", 4)
            .attr("fill", "red")
            .attr("opacity", 0.2)

            .on("click", function () {
                chartGroup.selectAll(".user-line")
                    .attr("opacity", 0.2)
                    .attr("stroke-widht", 2);

                chartGroup.selectAll(".user-dot")
                    .attr("opacity", 0.2)
                    .attr("r", 4);

                chartGroup.selectAll(`.user-line-${userId}`)
                    .attr("opacity", 1)
                    .attr("stroke-width", 4);

                chartGroup.selectAll(`.dot-${userId}`)
                    .attr("opacity", 1)
                    .attr("r", 6);
            })
        const tooltip = chartGroup.append("g")
            .style("pointer-events", "none") // ツールチップがマウスイベントを受けないようにする
            .style("opacity", 0); // 初期状態では非表示
        
        tooltip.append("rect")
            .attr("x", 0)
            .attr("y", 0)
            .attr("width", 120)
            .attr("height", 30)
            .attr("rx", 5)
            .attr("ry", 5)
            .attr("fill", "white")
            .attr("fill-opacity",1)
            .attr("stroke", "gray");
        
        tooltip.append("text")
            .attr("x", 10)
            .attr("y", 20)
            .attr("font-size", "12px")
            .attr("fill", "black");
    });

    // ズーム時の処理
    function zoomed(event) {
        // 新しいスケールを作成
        const newXScale = event.transform.rescaleX(xScale);

        // x軸を更新
        xAxis.call(d3.axisBottom(newXScale)
            .tickFormat(d3.timeFormat("%H:%M")));

        // 折れ線を更新
        chartGroup.selectAll(".user-line")
            .attr("d", d => {
                const newLineGenerator = d3.line()
                    .x(d => newXScale(new Date(d.timecreated * 1000)))
                    .y(d => {
                        const parsedName = stripHTMLTags(d.name);
                        const index = data1.findIndex(item => {
                            const parsedItem = stripHTMLTags(item);
                            return parsedItem.includes(parsedName);
                        });
                        return yScale(index);
                    })
                    .curve(d3.curveLinear);
                return newLineGenerator(d);
            });

        // 点を更新
        chartGroup.selectAll(".user-dot")
            .attr("cx", d => newXScale(new Date(d.timecreated * 1000)));
    }

    // ズームのリセット機能を追加
    function resetZoom() {
        svg.transition()
            .duration(750)
            .call(zoom.transform, d3.zoomIdentity);
    }

    // リセットボタンを追加
    svg.append("g")
        .attr("transform", `translate(${width + margin.left - 100}, ${margin.top - 20})`)
        .append("text")
        .attr("class", "reset-button")
        .attr("cursor", "pointer")
        .text("Reset Zoom")
        .on("click", () => {
            // ズームをリセット
            resetZoom();

            // 線とドットの状態をリセット
            chartGroup.selectAll(".user-line")
                .attr("opacity", 0.2)
                .attr("stroke-width", 2);

            chartGroup.selectAll(".user-dot")
                .attr("opacity", 0.2)
                .attr("r", 4);
        });
}
