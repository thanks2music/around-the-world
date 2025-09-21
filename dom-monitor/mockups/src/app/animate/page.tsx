'use client';

import { useState } from 'react';
import Image from 'next/image';

export default function AnimateMock() {
  const [showDetailPage, setShowDetailPage] = useState(false);

  const toggleDetailPage = () => {
    setShowDetailPage(!showDetailPage);
  };

  return (
    <div className="p-6 max-w-6xl mx-auto">
      <div className="flex justify-between items-center mb-8">
        <h1 className="text-2xl font-bold">ターゲットサイト (モックページ)</h1>
        <div className="flex gap-4">
          <button
            onClick={toggleDetailPage}
            className="px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600"
          >
            {showDetailPage ? '一覧ページに戻る' : '詳細ページを見る'}
          </button>
        </div>
      </div>

      <div className="border p-4 rounded bg-yellow-50 mb-8">
        <p className="text-sm text-gray-700">
          このページはPlaywrightテスト用のモックです。
          商品サムネイル画像をクリックすると詳細ページに遷移します。
        </p>
      </div>

      <div className="mt-8 bg-gray-100 p-4 rounded mb-8">
        <h2 className="text-lg font-bold mb-4">使用しているセレクタ一覧</h2>
        <table className="w-full border-collapse">
          <thead>
            <tr className="bg-gray-200">
              <th className="border p-2 text-left">項目</th>
              <th className="border p-2 text-left">セレクタ</th>
              <th className="border p-2 text-left">説明</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td className="border p-2">商品リンク</td>
              <td className="border p-2 font-mono text-sm">div.item_list_thumb a</td>
              <td className="border p-2 text-sm">商品一覧ページで、商品詳細へのリンク</td>
            </tr>
            <tr>
              <td className="border p-2">商品リンク（フォールバック）</td>
              <td className="border p-2 font-mono text-sm">
                div.item_list ul &gt; li:first-child &gt; h3 &gt; a
              </td>
              <td className="border p-2 text-sm">
                商品一覧ページで、商品詳細へのリンクのフォールバック
              </td>
            </tr>
            <tr>
              <td className="border p-2">詳細タイトル</td>
              <td className="border p-2 font-mono text-sm">div.item_overview_detail h1</td>
              <td className="border p-2 text-sm">商品詳細ページの商品タイトル</td>
            </tr>
            <tr>
              <td className="border p-2">価格</td>
              <td className="border p-2 font-mono text-sm">p.price.new_price</td>
              <td className="border p-2 text-sm">商品詳細ページの価格</td>
            </tr>
            <tr>
              <td className="border p-2">発売日</td>
              <td className="border p-2 font-mono text-sm">p.release &gt; span</td>
              <td className="border p-2 text-sm">商品詳細ページの発売日</td>
            </tr>
          </tbody>
        </table>
      </div>

      {!showDetailPage ? (
        // 一覧ページ
        <section>
          <div className="item_list">
            <div className="content_pager mb20">
              <div data-relingo-block="true" data-relin-paragraph="29">
                <strong>1</strong> | <a href="#">2</a> | <a href="#">3</a> | <a href="#">4</a>{' '}
                <a href="#">次へ&gt;&gt;</a>
              </div>
            </div>
            <ul className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <li className="border rounded shadow p-4">
                <div className="item_list_thumb">
                  <a
                    href="#"
                    onClick={e => {
                      e.preventDefault();
                      setShowDetailPage(true);
                    }}
                  >
                    {/* imgタグをImageコンポーネントに置き換え */}
                    <Image
                      src="https://tc-animate.techorus-cdn.com/resize_image/resize_image.php?image=JPRO_9784081024216.jpg"
                      width={178}
                      height={178}
                      alt=""
                      className="mx-auto cursor-pointer"
                    />
                  </a>
                </div>
                <h3 className="mt-3 font-bold">
                  <a
                    href="#"
                    onClick={e => {
                      e.preventDefault();
                      setShowDetailPage(true);
                    }}
                  >
                    商品名
                  </a>
                </h3>
                <div className="item_list_detail mt-2">
                  <p className="price new_price">1,540円(税込)</p>
                  <div className="item_list_status mt-2">
                    <p className="stock">
                      販売状況：<span className="_1">取り寄せ</span>
                    </p>
                    <p className="media">
                      カテゴリ：<a href="#">書籍</a>
                    </p>
                    <p className="release">
                      <span>発売日：2024/02/16 発売</span>
                    </p>
                  </div>
                </div>
              </li>

              <li className="border rounded shadow p-4">
                <div className="item_list_thumb">
                  <a
                    href="#"
                    onClick={e => {
                      e.preventDefault();
                    }}
                  >
                    {/* imgタグをImageコンポーネントに置き換え */}
                    <Image
                      src="https://tc-animate.techorus-cdn.com/resize_image/resize_image.php?image=JPRO_9784081025039.jpg"
                      width={178}
                      height={178}
                      alt=""
                      className="mx-auto"
                    />
                  </a>
                </div>
                <h3 className="mt-3 font-bold">
                  <a href="#">商品名</a>
                </h3>
                <div className="item_list_detail mt-2">
                  <p className="price new_price">2,200円(税込)</p>
                  <div className="item_list_status mt-2">
                    <p className="stock">
                      販売状況：<span className="_1">予約受付中</span>
                    </p>
                    <p className="media">
                      カテゴリ：<a href="#">書籍</a>
                    </p>
                    <p className="release">
                      <span>発売日：2025/05/29 発売</span>
                    </p>
                  </div>
                </div>
              </li>

              <li className="border rounded shadow p-4">
                <div className="item_list_thumb">
                  <a
                    href="#"
                    onClick={e => {
                      e.preventDefault();
                    }}
                  >
                    {/* imgタグをImageコンポーネントに置き換え */}
                    <Image
                      src="https://tc-animate.techorus-cdn.com/resize_image/resize_image.php?image=4550621333323_1_1744949106.jpg"
                      width={178}
                      height={178}
                      alt=""
                      className="mx-auto"
                    />
                  </a>
                </div>
                <h3 className="mt-3 font-bold">
                  <a href="#">商品名</a>
                </h3>
                <div className="item_list_detail mt-2">
                  <p className="price new_price">1,100円(税込)</p>
                  <div className="item_list_status mt-2">
                    <p className="stock">
                      販売状況：<span className="_1">予約受付中</span>
                    </p>
                    <p className="media">
                      カテゴリ：<a href="#">グッズ</a>
                    </p>
                    <p className="release">
                      <span>発売日：2025/07/26 発売</span>
                    </p>
                  </div>
                </div>
              </li>
            </ul>
            <div className="content_pager mt-6">
              <div data-relingo-block="true" data-relin-paragraph="133">
                <strong>1</strong> | <a href="#">2</a> | <a href="#">3</a> | <a href="#">4</a>{' '}
                <a href="#">次へ&gt;&gt;</a>
              </div>
            </div>
          </div>
        </section>
      ) : (
        // 詳細ページ
        <div className="contents_wrap pd20">
          <div className="main_contents item_upper">
            <section className="item_overview flex flex-col md:flex-row gap-6">
              <div className="item_images md:w-1/2">
                <div className="item_image_selected">
                  {/* imgタグをImageコンポーネントに置き換え */}
                  <Image
                    src="https://tc-animate.techorus-cdn.com/resize_image/resize_image.php?image=JPRO_9784081024216.jpg"
                    alt=""
                    className="w-full"
                    width={600}
                    height={600}
                  />
                </div>
                <div className="item_thumbs mt-4">
                  <div className="item_thumbs_inner">
                    <ul className="itemThumbnails flex space-x-2">
                      <li className="current">
                        {/* imgタグをImageコンポーネントに置き換え */}
                        <Image
                          src="https://tc-animate.techorus-cdn.com/resize_image/resize_image.php?image=JPRO_9784081024216.jpg"
                          alt=""
                          className="w-16 h-16 border"
                          width={64}
                          height={64}
                        />
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
              <div className="item_overview_detail md:w-1/2">
                <h1 className="text-xl font-bold">商品名</h1>

                <div className="item_price mt-4">
                  <div className="inner">
                    <p className="price new_price">
                      1,540円<span>(税込)</span>
                    </p>
                    <p className="text">
                      ポイント<span className="point_item">46pt</span>
                      <span className="text">(3％)付与</span>
                    </p>
                  </div>
                </div>
                <div className="item_status mt-4">
                  <p className="release">
                    <span className="num">2024/02/16 発売</span>
                  </p>
                  <div className="status">
                    <p className="stock block">
                      <span className="_1">〇 取り寄せ</span>
                    </p>
                  </div>

                  <div className="item_cart_box mt-6">
                    <div className="upper flex items-center gap-4">
                      <span className="amount_title">数量</span>
                      <div className="quantity">
                        <select className="border rounded p-1">
                          <option value="1">1</option>
                          <option value="2">2</option>
                          <option value="3">3</option>
                        </select>
                      </div>
                    </div>
                    <div className="cart_buttons_wrap mt-6">
                      <button className="bg-red-500 text-white px-6 py-3 rounded w-full">
                        カートに入れる
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </section>
          </div>
        </div>
      )}
    </div>
  );
}
